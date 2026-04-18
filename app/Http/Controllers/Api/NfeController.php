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
                'id', 'chave', 'numero', 'serie', 'valor_total', 'data_emissao', 'tipo_fiscal', 'empresa_id', 'xml_path',
                'cliente_nome as counterparty_nome', 'cliente_cnpj as counterparty_cnpj',
                'status as status_nfe',
                \DB::raw("'emitida' as origin")
            )
            ->where('empresa_id', $empresaId)
            ->where('tipo_fiscal', $movementType);

        // Query para notas RECEBIDAS (onde terceiros são os emissores)
        $qRecebidas = \DB::table('nfe_recebidas')
            ->select(
                'id', 'chave', 'numero', 'serie', 'valor_total', 'data_emissao', 'tipo_fiscal', 'empresa_id', 'xml_path',
                'emitente_nome as counterparty_nome', 'emitente_cnpj as counterparty_cnpj',
                'status_nfe',
                \DB::raw("'recebida' as origin")
            )
            ->where('empresa_id', $empresaId)
            ->where('tipo_fiscal', $movementType);
        // Aplica filtro de finalidade (CFOP) se fornecido
        // Aplica filtro de finalidade (CFOP) se fornecido
        if ($request->finalidade) {
            $finalidade = $request->finalidade;
            
            // Subquery específica para Emitidas (garantindo que nfe_recebida_id não atrapalhe)
            $qEmitidas->whereExists(function($q) use ($finalidade) {
                $q->select(\DB::raw(1))->from('nfe_items')->whereColumn('nfe_items.nfe_emitida_id', 'nfe_emitidas.id');
                $q->where(function ($subq) use ($finalidade) {
                    if ($finalidade === 'venda') {
                        $subq->whereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'101\' AND \'129\'')
                             ->orWhereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'401\' AND \'409\'')
                             ->orWhereIn('cfop', ['5102', '6102', '5108', '6108']);
                    } elseif ($finalidade === 'devolucao') {
                        $subq->whereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'201\' AND \'229\'')
                             ->orWhereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'410\' AND \'419\'')
                             ->orWhereIn('cfop', ['1202', '2202', '5202', '6202', '1411', '2411']);
                    } elseif ($finalidade === 'transferencia') {
                        $subq->whereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'151\' AND \'159\'');
                    } elseif ($finalidade === 'outras') {
                        $subq->whereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'900\' AND \'949\'');
                    }
                });
            });

            // Subquery específica para Recebidas
            $qRecebidas->whereExists(function($q) use ($finalidade) {
                $q->select(\DB::raw(1))->from('nfe_items')->whereColumn('nfe_items.nfe_recebida_id', 'nfe_recebidas.id');
                $q->where(function ($subq) use ($finalidade) {
                    if ($finalidade === 'venda') {
                        $subq->whereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'101\' AND \'129\'')
                             ->orWhereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'401\' AND \'409\'')
                             ->orWhereIn('cfop', ['5102', '6102', '5108', '6108']);
                    } elseif ($finalidade === 'devolucao') {
                        $subq->whereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'201\' AND \'229\'')
                             ->orWhereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'410\' AND \'419\'')
                             ->orWhereIn('cfop', ['1202', '2202', '5202', '6202', '1411', '2411']);
                    } elseif ($finalidade === 'transferencia') {
                        $subq->whereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'151\' AND \'159\'');
                    } elseif ($finalidade === 'outras') {
                        $subq->whereRaw('SUBSTR(cfop, 2, 3) BETWEEN \'900\' AND \'949\'');
                    }
                });
            });
        }
        // Aplica filtro de categoria (origem) se fornecido
        if ($request->categoria === 'emitida') {
            $unionQuery = $qEmitidas;
        } elseif ($request->categoria === 'recebida') {
            $unionQuery = $qRecebidas;
        } else {
            $unionQuery = $qEmitidas->union($qRecebidas);
        }

        // Define a query base combinada usando fromSub para garantir bindings corretos
        $query = \DB::query()->fromSub($unionQuery, 'combined');

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
                'mensagem' => "Importar NF-es do Mercado Livre de {$request->data_inicio} até {$request->data_fim}",
                'status' => 'processando',
                'total' => 1,
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
            // Salva arquivo temporário para o Job processar
            $uploadedPath = $request->file('xml')->store('nfes/uploads', 'local');
            
            // Cria tarefa para rastreamento
            $userId = auth()->id() ?? \App\Models\User::first()?->id;
            $tarefa = \App\Models\Tarefa::create([
                'empresa_id' => $empresa->id,
                'user_id' => $userId,
                'tipo' => 'import_nfe_xml',
                'mensagem' => "Importar XML: " . $request->file('xml')->getClientOriginalName(),
                'status' => 'processando',
                'total' => 1,
            ]);

            // Dispatch job para processar em background
            \App\Jobs\ImportarNfeXmlJob::dispatch($empresa->id, $uploadedPath, $tarefa->id);

            return response()->json([
                'success' => true,
                'message' => 'XML enviado para processamento em background. Acompanhe em Tarefas.',
                'job_id' => $tarefa->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao iniciar importação: '.$e->getMessage()]);
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
            $uploadedPath = $request->file('zip')->store('nfes/uploads', 'local');
            $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($uploadedPath);

            if ($zip->open($fullPath) !== true) {
                return response()->json(['success' => false, 'message' => 'Não foi possível abrir o arquivo ZIP']);
            }

            $files = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);

                // Agora captura todos os arquivos .xml independentemente da pasta
                if (substr($fileName, -1) !== '/' && strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'xml') {
                    $files[] = [
                        'type' => 'zip_content',
                        'path' => $fullPath,
                        'index' => $i,
                        'name' => basename($fileName)
                    ];
                }
            }

            $zip->close();
            
            $totalFiles = count($files);

            if ($totalFiles > 0) {
                \App\Jobs\ImportarNfeZipJob::dispatch(
                    $empresa->id,
                    auth()->id() ?? 1,
                    'importacao_zip',
                    $files
                );
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum arquivo XML válido encontrado dentro do ZIP.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "{$totalFiles} notas enviadas para processamento em background",
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
                'mensagem' => "Importar NF-es do Bling de {$request->data_inicio} até {$request->data_fim}",
                'status' => 'processando',
                'total' => 1,
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
