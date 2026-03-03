<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Integracao;
use App\Models\MarketplacePedido;
use App\Models\NfeEmitida;
use App\Models\ProductSku;
use App\Services\DanfeService;
use App\Services\MeliIntegrationService;
use Illuminate\Http\Request;

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

        if ($request->status_envio) {
            $query->where('status_envio', $request->status_envio);
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

        // Logistics filter (by mode in json_data)
        if ($request->logistics) {
            $query->where('json_data', 'like', '%"mode":"'.$request->logistics.'"%');
        }

        $orders = $query->orderBy('data_compra', 'desc')->paginate(50);

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

        $lucro = $this->calculateLucro($order);
        $lucroPercent = $this->calculateLucroPercent($order, $lucro);
        $itens = $this->getOrderItems($order);
        $nfeVinculada = $this->hasNfe($order);

        $jsonData = $order->json_data ?? [];
        $shipmentDetails = $jsonData['shipment_details'] ?? [];

        // Get item_id from first item for product link
        $itemId = null;
        if (! empty($jsonData['order_items'])) {
            $firstItem = $jsonData['order_items'][0];
            $itemId = $firstItem['item']['id'] ?? null;
        }

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
            'comprador_cpf' => $order->comprador_cpf,
            'comprador_cnpj' => $order->comprador_cnpj,
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
            'custo_total' => $lucro['custo'],
            'lucro' => $lucro['valor'],
            'lucro_percent' => $lucroPercent,
            'itens' => $itens,
            'nfe_vinculada' => $nfeVinculada,
            'data_compra' => $order->data_compra,
            'data_pagamento' => $order->data_pagamento,
            'data_envio' => $order->data_envio,
            'data_entrega' => $order->data_entrega,
            'codigo_rastreamento' => $order->codigo_rastreamento,
            'url_rastreamento' => $order->url_rastreamento,
            'item_id' => $itemId,
            'logistics' => [
                'mode' => $shipmentDetails['mode'] ?? null,
                'logistics_type' => $shipmentDetails['logistics_type'] ?? null,
                'label_pdf' => $shipmentDetails['label_pdf'] ?? null,
            ],
            'json_data' => $jsonData,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }

    private function calculateLucro($order)
    {
        $jsonData = $order->json_data ?? [];
        $orderItems = $jsonData['order_items'] ?? [];

        $custoTotal = 0;

        foreach ($orderItems as $item) {
            $sku = $item['item']['seller_sku'] ?? null;
            $quantidade = $item['quantity'] ?? 1;

            if ($sku) {
                $productSku = ProductSku::where('sku', $sku)->first();
                if ($productSku && $productSku->preco_custo) {
                    $custoTotal += floatval($productSku->preco_custo) * $quantidade;
                }
            }
        }

        $valorLiquido = floatval($order->valor_liquido ?? 0);
        $lucro = $valorLiquido - $custoTotal;

        return [
            'valor' => $lucro,
            'custo' => $custoTotal,
        ];
    }

    private function calculateLucroPercent($order, $lucro)
    {
        $valorLiquido = floatval($order->valor_liquido ?? 0);
        if ($valorLiquido <= 0) {
            return 0;
        }

        return round(($lucro['valor'] / $valorLiquido) * 100, 1);
    }

    private function getOrderItems($order)
    {
        $jsonData = $order->json_data ?? [];
        $orderItems = $jsonData['order_items'] ?? [];

        $itens = [];

        foreach ($orderItems as $item) {
            $sku = $item['item']['seller_sku'] ?? null;
            $itemId = $item['item']['id'] ?? null;
            $titulo = $item['item']['title'] ?? 'Produto sem título';
            $quantidade = $item['quantity'] ?? 1;
            $precoUnitario = floatval($item['unit_price'] ?? 0);
            $precoTotal = $precoUnitario * $quantidade;
            $thumbnail = $item['item']['thumbnail'] ?? $item['item']['picture'] ?? null;

            $custoUnitario = 0;
            if ($sku) {
                $productSku = ProductSku::where('sku', $sku)->first();
                if ($productSku && $productSku->preco_custo) {
                    $custoUnitario = floatval($productSku->preco_custo);
                }
            }

            $itens[] = [
                'sku' => $sku,
                'item_id' => $itemId,
                'titulo' => $titulo,
                'titulo_reduzido' => mb_strimwidth($titulo, 0, 35, '...'),
                'quantidade' => $quantidade,
                'preco_unitario' => $precoUnitario,
                'preco_total' => $precoTotal,
                'custo_unitario' => $custoUnitario,
                'custo_total' => $custoUnitario * $quantidade,
                'thumbnail' => $thumbnail,
            ];
        }

        return $itens;
    }

    private function hasNfe($order)
    {
        $pedidoId = $order->pedido_id;

        if (! $pedidoId) {
            return false;
        }

        $nfe = NfeEmitida::where('pedido_marketplace', $pedidoId)->first();

        return $nfe ? [
            'numero' => $nfe->numero,
            'chave' => $nfe->chave,
            'status' => $nfe->status,
        ] : null;
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

    public function integrations(Request $request)
    {
        $empresaId = (int) $request->get('empresa_id', session('empresa_id', 6));

        $integrations = Integracao::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('marketplace', 'mercadolivre')
            ->get(['id', 'marketplace', 'nome_conta', 'external_user_id'])
            ->map(fn ($i) => [
                'id' => $i->id,
                'marketplace' => $i->marketplace,
                'nome_conta' => $i->nome_conta,
                'user_id' => $i->external_user_id,
            ]);

        return response()->json([
            'mercadolivre' => $integrations->values(),
        ]);
    }

    public function sync(Request $request)
    {
        $empresaId = (int) $request->get('empresa_id', session('empresa_id', 6));
        $marketplace = $request->get('marketplace', 'mercadolivre');

        try {
            if ($marketplace === 'mercadolivre') {
                $service = new MeliIntegrationService($empresaId);

                if (! $service->isConnected()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mercado Livre não conectado para esta empresa (empresa: '.$empresaId.')',
                    ], 400);
                }

                $result = $service->syncOrders();

                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'message' => 'Marketplace não suportado: '.$marketplace,
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao sincronizar: '.$e->getMessage(),
            ], 500);
        }
    }

    public function refresh(Request $request, int $id)
    {
        $empresaId = (int) $request->get('empresa_id', session('empresa_id', 6));

        try {
            $pedido = MarketplacePedido::where('id', $id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            if ($pedido->marketplace !== 'mercadolivre') {
                return response()->json([
                    'success' => false,
                    'message' => 'Atualização disponível apenas para Mercado Livre',
                ], 400);
            }

            $service = new MeliIntegrationService($empresaId);
            $result = $service->refreshOrder($id);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar pedido: '.$e->getMessage(),
            ], 500);
        }
    }

    public function danfe(Request $request, int $id)
    {
        $empresaId = (int) $request->get('empresa_id', session('empresa_id', 6));

        $pedido = MarketplacePedido::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        $nfe = NfeEmitida::where('pedido_marketplace', $pedido->pedido_id)->first();

        if (! $nfe) {
            return response()->json(['error' => 'NFe não vinculada a este pedido'], 404);
        }

        $empresa = Empresa::find($empresaId);

        try {
            $xml = storage_path('app/'.$nfe->xml_path);

            if (! file_exists($xml)) {
                return response()->json(['error' => 'XML da NFe não encontrado'], 404);
            }

            $xmlContent = file_get_contents($xml);
            $danfeService = new DanfeService;
            $pdf = $danfeService->gerarDanfeA4($xmlContent, $empresa->toArray());

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="DANFE_'.$nfe->numero.'.pdf"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar DANFE: '.$e->getMessage()], 500);
        }
    }

    public function danfeSimplificada(Request $request, int $id)
    {
        $empresaId = (int) $request->get('empresa_id', session('empresa_id', 6));

        $pedido = MarketplacePedido::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        $nfe = NfeEmitida::where('pedido_marketplace', $pedido->pedido_id)->first();

        if (! $nfe) {
            return response()->json(['error' => 'NFe não vinculada a este pedido'], 404);
        }

        $empresa = Empresa::find($empresaId);

        try {
            $xml = storage_path('app/'.$nfe->xml_path);

            if (! file_exists($xml)) {
                return response()->json(['error' => 'XML da NFe não encontrado'], 404);
            }

            $xmlContent = file_get_contents($xml);
            $danfeService = new DanfeService;
            $html = $danfeService->gerarDanfeSimplificado($xmlContent, $empresa->toArray());

            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'inline; filename="DANFE_SIMPLIFICADO_'.$nfe->numero.'.html"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar DANFE Simplificado: '.$e->getMessage()], 500);
        }
    }

    public function etiqueta(Request $request, int $id)
    {
        $empresaId = (int) $request->get('empresa_id', session('empresa_id', 6));

        $pedido = MarketplacePedido::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        $nfe = NfeEmitida::where('pedido_marketplace', $pedido->pedido_id)->first();

        if (! $nfe) {
            return response()->json(['error' => 'NFe não vinculada a este pedido'], 404);
        }

        $empresa = Empresa::find($empresaId);

        try {
            $xml = storage_path('app/'.$nfe->xml_path);

            if (! file_exists($xml)) {
                return response()->json(['error' => 'XML da NFe não encontrado'], 404);
            }

            $xmlContent = file_get_contents($xml);
            $danfeService = new DanfeService;
            $html = $danfeService->gerarEtiqueta($xmlContent, $empresa->toArray());

            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'inline; filename="ETIQUETA_'.$pedido->pedido_id.'.html"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao gerar Etiqueta: '.$e->getMessage()], 500);
        }
    }

    public function etiquetaMeli(Request $request, int $id)
    {
        $empresaId = (int) $request->get('empresa_id', session('empresa_id', 6));

        $pedido = MarketplacePedido::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $pedido) {
            return response()->json(['error' => 'Pedido não encontrado'], 404);
        }

        $jsonData = $pedido->json_data ?? [];
        $shippingId = $jsonData['shipping']['id'] ?? null;

        if (! $shippingId) {
            return response()->json(['error' => 'ID do shipment não encontrado'], 404);
        }

        try {
            $service = new MeliIntegrationService($empresaId);
            $shipment = $service->getShipment($shippingId);

            if (isset($shipment['error'])) {
                return response()->json(['error' => $shipment['error']], 400);
            }

            $labelUrl = $shipment['label'] ?? null;
            $labelPdfUrl = $shipment['label_pdf'] ?? null;

            if ($labelPdfUrl) {
                return redirect($labelPdfUrl);
            }

            if ($labelUrl) {
                return redirect($labelUrl);
            }

            return response()->json(['error' => 'Etiqueta não disponível no momento'], 404);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao buscar etiqueta: '.$e->getMessage()], 500);
        }
    }
}
