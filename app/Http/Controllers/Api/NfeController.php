<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use Illuminate\Http\Request;

class NfeController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->get('empresa', $request->get('empresa_id', session('empresa_id', 6)));
        $viewArg = $request->get('tipo', $request->get('view', 'saidas')); // Default para saídas
        
        // Normalização semântica: Entradas vs Saídas
        $movementType = ($viewArg === 'entradas' || $viewArg === 'recebidas') ? 'entrada' : 'saida';

        $empresa = \App\Models\Empresa::find($empresaId);
        $empresaNome = $empresa ? $empresa->nome : 'Empresa';
        $empresaCnpj = $empresa ? $empresa->cnpj : '';

        // Query para notas EMITIDAS (onde a empresa é a emissora)
        $qEmitidas = \DB::table('nfe_emitidas')
            ->select(
                'id', 'chave', 'numero', 'serie', 'valor_total', 'data_emissao', 'tipo_fiscal', 'empresa_id',
                'cliente_nome as counterparty_nome', 'cliente_cnpj as counterparty_cnpj',
                'status as status_nfe',
                \DB::raw("'emitida' as origin")
            )
            ->where('empresa_id', $empresaId)
            ->where('tipo_fiscal', $movementType);

        // Query para notas RECEBIDAS (onde terceiros são os emissores)
        $qRecebidas = \DB::table('nfe_recebidas')
            ->select(
                'id', 'chave', 'numero', 'serie', 'valor_total', 'data_emissao', 'tipo_fiscal', 'empresa_id',
                'emitente_nome as counterparty_nome', 'emitente_cnpj as counterparty_cnpj',
                'status_nfe',
                \DB::raw("'recebida' as origin")
            )
            ->where('empresa_id', $empresaId)
            ->where('tipo_fiscal', $movementType);

        // Define a query base combinada
        $unionQuery = $qEmitidas->union($qRecebidas);
        $query = \DB::table(\DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery);

        // Filtros de busca
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('chave', 'like', "%{$request->search}%")
                    ->orWhere('numero', 'like', "%{$request->search}%")
                    ->orWhere('counterparty_nome', 'like', "%{$request->search}%");
            });
        }

        if ($request->situacao || $request->status) {
            $query->where('status_nfe', $request->situacao ?: $request->status);
        }

        if ($request->data_inicio || $request->data_de) {
            $query->whereDate('data_emissao', '>=', $request->data_inicio ?: $request->data_de);
        }

        if ($request->data_fim || $request->data_ate) {
            $query->whereDate('data_emissao', '<=', $request->data_fim ?: $request->data_ate);
        }

        // Agregados
        $aggregates = (clone $query)->selectRaw('COALESCE(SUM(valor_total), 0) as total_value, COUNT(*) as total_count')->first();
        $canceladasCount = (clone $query)->where('status_nfe', 'cancelada')->count();

        // Paginação
        $nfes = $query->orderBy('data_emissao', 'desc')->paginate($request->per_page ?? 100);

        return response()->json([
            'aggregates' => [
                'total_value' => floatval($aggregates->total_value ?? 0),
                'total_count' => intval($aggregates->total_count ?? 0),
                'canceladas_count' => $canceladasCount,
            ],
            'data' => collect($nfes->items())->map(function ($nfe) use ($empresaNome, $empresaCnpj) {
                return [
                    'id' => $nfe->id,
                    'chave' => $nfe->chave,
                    'numero' => $nfe->numero,
                    'serie' => $nfe->serie,
                    // Lógica Dinâmica de Exibição
                    'emitente_nome' => ($nfe->origin === 'emitida') ? $empresaNome : $nfe->counterparty_nome,
                    'emitente_cnpj' => ($nfe->origin === 'emitida') ? $empresaCnpj : $nfe->counterparty_cnpj,
                    'cliente_nome' => ($nfe->origin === 'emitida') ? $nfe->counterparty_nome : $empresaNome,
                    'cliente_cnpj' => ($nfe->origin === 'emitida') ? $nfe->counterparty_cnpj : $empresaCnpj,
                    'counterparty_nome' => $nfe->counterparty_nome,
                    'counterparty_cnpj' => $nfe->counterparty_cnpj,
                    'valor_total' => floatval($nfe->valor_total),
                    'data_emissao' => $nfe->data_emissao,
                    'status_nfe' => $nfe->status_nfe,
                    'tipo_fiscal' => $nfe->tipo_fiscal,
                    'categoria' => $nfe->origin,
                    'xml_path' => $nfe->xml_path,
                ];
            }),
            'current_page' => $nfes->currentPage(),
            'last_page' => $nfes->lastPage(),
            'total' => $nfes->total(),
            'from' => $nfes->firstItem(),
            'to' => $nfes->lastItem(),
            'empresa_stats' => [
                'last_nsu' => $empresa?->last_nsu ?? 0,
                'last_sefaz_at' => $empresa?->updated_at ? $empresa->updated_at->format('d/m/Y H:i:s') : 'Nunca',
            ],
        ]);
    }

    public function show($id)
    {
        // Tenta encontrar em ambas as tabelas
        $nfe = NfeRecebida::find($id) ?? NfeEmitida::find($id);

        if (! $nfe) {
            return response()->json(['error' => 'NF-e não encontrada'], 404);
        }

        $categoria = ($nfe instanceof NfeEmitida) ? 'emitida' : 'recebida';

        $data = $nfe->toArray();
        $data['categoria'] = $categoria;
        
        // Ensure values are floats for JSON
        $data['valor_total'] = floatval($nfe->valor_total);
        $data['valor_frete'] = floatval($nfe->valor_frete ?? 0);
        
        return response()->json($data);
    }


    public function import(Request $request)
    {
        $empresaId = $request->get('empresa', session('empresa_id', 6));
        $empresa = \App\Models\Empresa::find($empresaId);

        if (! $empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada'], 404);
        }

        try {
            $fiscalService = new \App\Services\FiscalService;
            // Aqui chamaria o serviço que consulta a SEFAZ
            // Como é uma simulação/exemplo de fluxo:

            $nsuInicial = $empresa->last_nsu;

            // Simulação de busca (substituir pela lógica real de consulta SEFAZ)
            // $result = $sefazService->consultarNotas($empresa);

            // Exemplo de retorno esperado do serviço real:
            $result = [
                'nsu_inicial' => $nsuInicial,
                'nsu_final' => $nsuInicial + rand(1, 5),
                'qtd_notas' => rand(0, 3),
                'mensagens' => 'Consulta realizada com sucesso',
            ];

            // Atualiza o last_nsu da empresa
            $empresa->update([
                'last_nsu' => $result['nsu_final'],
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Consulta SEFAZ concluída',
                'nsu_inicial' => $result['nsu_inicial'],
                'nsu_final' => $result['nsu_final'],
                'qtd_notas' => $result['qtd_notas'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro SEFAZ: '.$e->getMessage()], 500);
        }
    }

    public function reprocessAssociation(Request $request)
    {
        $ids = $request->input('ids', []);
        $type = $request->input('type', 'emitida');

        $model = $type === 'recebida' ? \App\Models\NfeRecebida::class : \App\Models\NfeEmitida::class;

        $notas = $model::with(['itens', 'empresa'])->whereIn('id', $ids)->get();
        $totalAssociated = 0;

        foreach ($notas as $nota) {
            $grupoId = $nota->empresa?->grupo_id;
            if (! $grupoId) {
                continue;
            }

            foreach ($nota->itens as $item) {
                if ($item->associateProduct($grupoId)) {
                    $totalAssociated++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$totalAssociated} produtos associados",
        ]);
    }

    /**
     * Importa NF-e do Mercado Livre por data
     */
    public function importMeli(Request $request)
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date',
        ]);

        // Usa empresa_id do request ou da session
        $empresaId = $request->input('empresa_id', session('empresa_id', 6));
        $empresa = \App\Models\Empresa::find($empresaId);

        if (! $empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada']);
        }

        // Verificar se tem integração com Mercado Livre
        $integracao = $empresa->integracoes()->where('marketplace', 'mercadolivre')->first();

        if (! $integracao) {
            return response()->json(['success' => false, 'message' => 'Integração do Mercado Livre não encontrada. Configure em Integrações.']);
        }

        if ($integracao->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Token do Mercado Livre expirado. Reconecte a integração.']);
        }

        try {
            // Criar tarefa para rastrear
            $userId = auth()->id() ?? \App\Models\User::first()?->id;
            $tarefa = \App\Models\Tarefa::create([
                'empresa_id' => $empresa->id,
                'user_id' => $userId,
                'tipo' => 'import_nfe_meli',
                'descricao' => "Importar NF-es do Mercado Livre de {$request->data_inicio} até {$request->data_fim}",
                'status' => 'processando',
                'progresso' => 0,
            ]);

            // Dispatch job para processar em background
            \App\Jobs\ImportarNFeMeliJob::dispatch($empresa, $request->data_inicio, $request->data_fim, $tarefa->id);

            return response()->json([
                'success' => true,
                'job_id' => $tarefa->id,
                'message' => 'Importação iniciada em background',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro: '.$e->getMessage()]);
        }
    }

    /**
     * Importa NF-e de arquivo XML
     */
    public function importXml(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml',
        ]);

        $empresaId = $request->input('empresa_id', session('empresa_id', 6));
        $empresa = \App\Models\Empresa::find($empresaId);

        if (! $empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada']);
        }

        try {
            $xmlContent = file_get_contents($request->file('xml')->getRealPath());

            $fiscalService = new \App\Services\FiscalService;
            $result = $fiscalService->importXml($xmlContent, $empresa->id);

            return response()->json([
                'success' => true,
                'message' => 'XML importado com sucesso',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro: '.$e->getMessage()]);
        }
    }

    /**
     * Importa NF-e de arquivo ZIP
     */
    public function importZip(Request $request)
    {
        $request->validate([
            'zip' => 'required|file|mimes:zip',
        ]);

        $empresaId = $request->input('empresa_id', session('empresa_id', 6));
        $empresa = \App\Models\Empresa::find($empresaId);

        if (! $empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada']);
        }

        try {
            $zip = new \ZipArchive;
            $filePath = $request->file('zip')->getRealPath();

            if ($zip->open($filePath) !== true) {
                return response()->json(['success' => false, 'message' => 'Não foi possível abrir o arquivo ZIP']);
            }

            $fiscalService = new \App\Services\FiscalService;
            $processed = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);

                if (substr($fileName, -1) === '/' || pathinfo($fileName, PATHINFO_EXTENSION) !== 'xml') {
                    continue;
                }

                $xmlContent = $zip->getFromIndex($i);
                if ($xmlContent) {
                    try {
                        $fiscalService->importXml($xmlContent, $empresa->id);
                        $processed++;
                    } catch (\Exception $e) {
                        \Log::error('Erro ao processar XML do ZIP: '.$e->getMessage());
                    }
                }
            }

            $zip->close();

            return response()->json([
                'success' => true,
                'message' => "{$processed} notas importadas com sucesso",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro: '.$e->getMessage()]);
        }
    }

    /**
     * Importa NF-e do Bling por data
     */
    public function importBling(Request $request)
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date',
        ]);

        $empresaId = session('empresa_id', 6);
        $empresa = \App\Models\Empresa::find($empresaId);

        if (! $empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada']);
        }

        // Verificar se tem integração com Bling
        $blingConfig = $empresa->blingConfig()->first();

        if (! $blingConfig) {
            return response()->json(['success' => false, 'message' => 'Integração do Bling não encontrada. Configure em Integrações.']);
        }

        try {
            // Criar tarefa para rastrear
            $userId = auth()->id() ?? \App\Models\User::first()?->id;
            $tarefa = \App\Models\Tarefa::create([
                'empresa_id' => $empresa->id,
                'user_id' => $userId,
                'tipo' => 'import_nfe_bling',
                'descricao' => "Importar NF-es do Bling de {$request->data_inicio} até {$request->data_fim}",
                'status' => 'processando',
                'progresso' => 0,
            ]);

            // Dispatch job para processar em background
            \App\Jobs\ImportNfeBlingJob::dispatch(
                $empresa->id,
                auth()->id(),
                $tarefa->id,
                $request->data_inicio,
                $request->data_fim
            );

            return response()->json([
                'success' => true,
                'job_id' => $tarefa->id,
                'message' => 'Importação do Bling iniciada com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro: '.$e->getMessage()]);
        }
    }
}
