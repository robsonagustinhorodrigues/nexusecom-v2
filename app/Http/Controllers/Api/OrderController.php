<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplacePedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->get('empresa_id', session('empresa_id', 6));
        
        $query = MarketplacePedido::where('empresa_id', $empresaId);
        
        // Filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('pedido_id', 'like', "%{$request->search}%")
                  ->orWhere('comprador_nome', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->marketplace) {
            $query->where('marketplace', $request->marketplace);
        }
        
        if ($request->data_de) {
            $query->whereDate('data_compra', '>=', $request->data_de);
        }
        
        if ($request->data_ate) {
            $query->whereDate('data_compra', '<=', $request->data_ate);
        }
        
        $orders = $query->orderBy('data_compra', 'desc')->paginate(20);
        
        return response()->json([
            'data' => $orders->map(function ($order) {
                return $this->formatOrder($order);
            }),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'total' => $orders->total(),
            'from' => $orders->firstItem(),
            'to' => $orders->lastItem(),
        ]);
    }

    private function formatOrder($order)
    {
        $taxas = floatval($order->valor_taxa_platform ?? 0) 
               + floatval($order->valor_taxa_pagamento ?? 0) 
               + floatval($order->valor_taxa_fixa ?? 0)
               + floatval($order->valor_outros ?? 0);
        
        return [
            'id' => $order->id,
            'pedido_id' => $order->pedido_id,
            'external_id' => $order->external_id,
            'marketplace' => $order->marketplace,
            'status' => $order->status,
            'status_pagamento' => $order->status_pagamento,
            'status_envio' => $order->status_envio,
            'comprador_nome' => $order->comprador_nome,
            'comprador_email' => $order->comprador_email,
            'telefone' => $order->telefone,
            'endereco' => $order->endereco,
            'cidade' => $order->cidade,
            'estado' => $order->estado,
            'cep' => $order->cep,
            'valor_total' => floatval($order->valor_total),
            'valor_frete' => floatval($order->valor_frete),
            'valor_desconto' => floatval($order->valor_desconto),
            'valor_produtos' => floatval($order->valor_produtos),
            'taxas' => $taxas,
            'valor_liquido' => floatval($order->valor_liquido),
            'data_compra' => $order->data_compra,
            'data_pagamento' => $order->data_pagamento,
            'data_envio' => $order->data_envio,
            'data_entrega' => $order->data_entrega,
            'codigo_rastreamento' => $order->codigo_rastreamento,
            'url_rastreamento' => $order->url_rastreamento,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }
    
    public function show($id)
    {
        $order = MarketplacePedido::findOrFail($id);
        
        return response()->json($this->formatOrder($order));
    }
    
    public function update(Request $request, $id)
    {
        $order = MarketplacePedido::findOrFail($id);
        
        $order->update($request->only([
            'status',
            'codigo_rastreamento',
            'url_rastreamento',
            'status_envio',
        ]));
        
        return response()->json(['success' => true, 'message' => 'Pedido atualizado']);
    }
    
    public function sync(Request $request)
    {
        // Placeholder - in real implementation would call sync job
        return response()->json([
            'success' => true,
            'message' => 'Sincronização iniciada'
        ]);
    }
}
