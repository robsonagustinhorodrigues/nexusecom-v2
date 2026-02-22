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
        $view = $request->get('tipo', $request->get('view', 'recebidas'));

        if ($view === 'emitidas') {
            $query = NfeEmitida::where('empresa_id', $empresaId);
        } else {
            $query = NfeRecebida::where('empresa_id', $empresaId);
        }

        // Filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('chave', 'like', "%{$request->search}%")
                    ->orWhere('numero', 'like', "%{$request->search}%");
            });
        }

        // Filter by situacao (status)
        if ($request->situacao) {
            $query->where('status_nfe', $request->situacao);
        }

        if ($request->status) {
            $query->where('status_nfe', $request->status);
        }

        if ($request->nome) {
            $campoNome = $view === 'recebidas' ? 'emitente_nome' : 'cliente_nome';
            $query->where($campoNome, 'like', "%{$request->nome}%");
        }

        // Date filters from frontend
        if ($request->data_inicio) {
            $query->whereDate('data_emissao', '>=', $request->data_inicio);
        }

        if ($request->data_fim) {
            $query->whereDate('data_emissao', '<=', $request->data_fim);
        }

        if ($request->data_de) {
            $query->whereDate('data_emissao', '>=', $request->data_de);
        }

        if ($request->data_ate) {
            $query->whereDate('data_emissao', '<=', $request->data_ate);
        }

        $nfes = $query->orderBy('data_emissao', 'desc')->paginate($request->per_page ?? 100);

        return response()->json([
            'data' => $nfes->map(function ($nfe) {
                return [
                    'id' => $nfe->id,
                    'chave' => $nfe->chave,
                    'numero' => $nfe->numero,
                    'serie' => $nfe->serie,
                    'emitente_nome' => $nfe->emitente_nome ?? $nfe->cliente_nome,
                    'emitente_cnpj' => $nfe->emitente_cnpj ?? $nfe->cliente_cnpj,
                    'cliente_nome' => $nfe->cliente_nome,
                    'cliente_cnpj' => $nfe->cliente_cnpj,
                    'valor_total' => floatval($nfe->valor_total),
                    'data_emissao' => $nfe->data_emissao,
                    'data_recebimento' => $nfe->data_recebimento,
                    'status_nfe' => $nfe->status_nfe,
                    'status_manifestacao' => $nfe->status_manifestacao ?? null,
                    'xml_path' => $nfe->xml_path,
                    'devolucao' => $nfe->devolucao ?? false,
                    'created_at' => $nfe->created_at,
                ];
            }),
            'current_page' => $nfes->currentPage(),
            'last_page' => $nfes->lastPage(),
            'total' => $nfes->total(),
            'from' => $nfes->firstItem(),
            'to' => $nfes->lastItem(),
        ]);
    }

    public function show($id)
    {
        // Try recebidas first, then emitidas
        $nfe = NfeRecebida::find($id);
        $view = 'recebidas';

        if (! $nfe) {
            $nfe = NfeEmitida::find($id);
            $view = 'emitidas';
        }

        if (! $nfe) {
            return response()->json(['error' => 'NF-e não encontrada'], 404);
        }

        return response()->json([
            'id' => $nfe->id,
            'chave' => $nfe->chave,
            'numero' => $nfe->numero,
            'serie' => $nfe->serie,
            'emitente_nome' => $nfe->emitente_nome ?? $nfe->cliente_nome,
            'emitente_cnpj' => $nfe->emitente_cnpj ?? $nfe->cliente_cnpj,
            'cliente_nome' => $nfe->cliente_nome,
            'cliente_cnpj' => $nfe->cliente_cnpj,
            'valor_total' => floatval($nfe->valor_total),
            'valor_frete' => floatval($nfe->valor_frete ?? 0),
            'valor_desconto' => floatval($nfe->valor_desconto ?? 0),
            'valor_icms' => floatval($nfe->valor_icms ?? 0),
            'data_emissao' => $nfe->data_emissao,
            'data_recebimento' => $nfe->data_recebimento,
            'status_nfe' => $nfe->status_nfe,
            'status_manifestacao' => $nfe->status_manifestacao ?? null,
            'xml_path' => $nfe->xml_path,
            'devolucao' => $nfe->devolucao ?? false,
            'view' => $view,
        ]);
    }

    public function import(Request $request)
    {
        // Placeholder for import functionality
        return response()->json([
            'success' => true,
            'message' => 'Importação iniciada',
        ]);
    }

    public function reprocessAssociation(Request $request)
    {
        $ids = $request->input('ids', []);
        $type = $request->input('type', 'emitida');
        
        $model = $type === 'recebida' ? \App\Models\NfeRecebida::class : \App\Models\NfeEmitida::class;
        
        $notas = $model::with('itens')->whereIn('id', $ids)->get();
        $totalAssociated = 0;
        
        foreach ($notas as $nota) {
            $grupoId = $nota->empresa?->grupo_id;
            if (!$grupoId) continue;
            
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

        $empresaId = session('empresa_id', 6);
        $empresa = \App\Models\Empresa::find($empresaId);

        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada']);
        }

        // Verificar se tem integração com Mercado Livre
        $integracao = $empresa->integracoes()->where('marketplace', 'mercadolivre')->first();
        
        if (!$integracao) {
            return response()->json(['success' => false, 'message' => 'Integração do Mercado Livre não encontrada. Configure em Integrações.']);
        }

        if ($integracao->isExpired()) {
            return response()->json(['success' => false, 'message' => 'Token do Mercado Livre expirado. Reconecte a integração.']);
        }

        try {
            // Criar tarefa para rastrear
            $tarefa = \App\Models\Tarefa::create([
                'empresa_id' => $empresa->id,
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
            return response()->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
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

        $empresaId = session('empresa_id', 6);
        $empresa = \App\Models\Empresa::find($empresaId);

        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada']);
        }

        try {
            $xmlContent = file_get_contents($request->file('xml')->getRealPath());
            
            $fiscalService = new \App\Services\FiscalService($empresa);
            $result = $fiscalService->processXmlContent($empresa, $xmlContent);

            return response()->json([
                'success' => true,
                'message' => 'XML importado com sucesso',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
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

        $empresaId = session('empresa_id', 6);
        $empresa = \App\Models\Empresa::find($empresaId);

        if (!$empresa) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada']);
        }

        try {
            $zip = new \ZipArchive();
            $filePath = $request->file('zip')->getRealPath();
            
            if ($zip->open($filePath) !== true) {
                return response()->json(['success' => false, 'message' => 'Não foi possível abrir o arquivo ZIP']);
            }

            $processed = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);
                
                if (substr($fileName, -1) === '/' || pathinfo($fileName, PATHINFO_EXTENSION) !== 'xml') {
                    continue;
                }

                $xmlContent = $zip->getFromIndex($i);
                if ($xmlContent) {
                    \App\Jobs\ImportarNFeMeliJob::dispatch($empresa, $xmlContent);
                    $processed++;
                }
            }

            $zip->close();

            return response()->json([
                'success' => true,
                'message' => "{$processed} notas enviadas para processamento",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        }
    }
}
