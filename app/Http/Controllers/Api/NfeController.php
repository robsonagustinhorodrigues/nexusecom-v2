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
}
