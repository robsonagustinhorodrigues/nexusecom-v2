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
use App\Services\AmazonSpApiService;
use App\Services\OrderProfitService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private OrderProfitService $profitService;

    public function __construct()
    {
        $this->profitService = new OrderProfitService();
    }


    public function index(Request $request)
    {
        $empresaId = $request->get('empresa_id', session('empresa_id', 6));

        $query = MarketplacePedido::where('empresa_id', $empresaId)->with('nfeEmitida');

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

        // Sorting
        $allowedSortFields = [
            'data_compra' => 'data_compra',
            'valor_total' => 'valor_total',
            'lucro' => 'lucro',
            'lucro_percent' => 'lucro_percent',
            'valor_produtos' => 'valor_produtos',
        ];

        $sortBy = $request->get('sort_by');
        $sortDir = strtolower($request->get('sort_dir')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'lucro_percent') {
            $query->orderByRaw('CASE WHEN valor_total > 0 THEN lucro / valor_total ELSE 0 END ' . $sortDir);
        } else {
            $column = $allowedSortFields[$sortBy] ?? 'data_compra';
            $query->orderBy($column, $sortDir);
        }

        // Calculate global totals for filtered query
        $totalsQuery = clone $query;
        $stats = $totalsQuery->reorder()->selectRaw('COUNT(*) as total_pedidos, SUM(valor_total) as total_faturamento, SUM(lucro) as total_lucro')->first();

        $orders = $query->paginate(50);

        // Gather all SKUs from orders
        $skus = collect();
        foreach ($orders as $order) {
            $jsonData = $order->json_data ?? [];
            $orderItems = $jsonData['order_items'] ?? [];
            foreach ($orderItems as $item) {
                if (!empty($item['item']['seller_sku'])) {
                    $skus->push($item['item']['seller_sku']);
                }
            }
        }
        
        $skusInfo = ProductSku::whereIn('sku', $skus->unique()->toArray())->get()->keyBy('sku');
        $empresa = Empresa::find($empresaId);

        return response()->json([
            'data' => $orders->map(function ($order) use ($skusInfo, $empresa) {
                return $this->formatOrder($order, $skusInfo, $empresa);
            }),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'total' => $orders->total(),
            'from' => $orders->firstItem(),
            'to' => $orders->lastItem(),
            'stats' => [
                'total_pedidos' => $stats->total_pedidos,
                'total_faturamento' => $stats->total_faturamento,
                'total_lucro' => $stats->total_lucro,
            ],
        ]);
    }

    private function formatOrder($order, $skusInfo = null, $empresa = null)
    {
        $taxas = floatval($order->valor_taxa_platform ?? 0)
               + floatval($order->valor_taxa_pagamento ?? 0)
               + floatval($order->valor_taxa_fixa ?? 0)
               + floatval($order->valor_outros ?? 0);

        $valorImposto = floatval($order->valor_imposto ?? 0);
        $valorProdutos = floatval($order->valor_produtos ?? 0);

        if ($valorImposto <= 0 && $empresa && floatval($empresa->aliquota_simples) > 0) {
            $valorImposto = $valorProdutos * (floatval($empresa->aliquota_simples) / 100);
        }

        $profit = $this->profitService->calculateOrderProfit($order, $empresa, $skusInfo);
        $itens = $profit['itens'];
        $valorLiquido = $profit['valor_liquido'];
        $valorFrete = $profit['valor_frete'];
        $lucroValor = $profit['lucro_valor'];
        $lucroPercent = $profit['lucro_percent'];
        $custoTotal = $profit['custo_total'];

        $nfeVinculada = $this->hasNfe($order);

        $jsonData = $order->json_data ?? [];
        $shipmentDetails = $jsonData['shipment_details'] ?? [];

        $itemId = null;
        if (! empty($jsonData['order_items'])) {
            $firstItem = $jsonData['order_items'][0];
            $itemId = $firstItem['item']['id'] ?? null;
        }

        $buyerNickname = $jsonData['buyer']['nickname'] ?? null;

        return [
            'id' => $order->id,
            'pedido_id' => $order->pedido_id,
            'pack_id' => $jsonData['pack_id'] ?? null,
            'external_id' => $order->external_id,
            'marketplace' => $order->marketplace,
            'status' => $order->status,
            'status_pagamento' => $order->status_pagamento,
            'status_envio' => $order->status_envio,
            'comprador_apelido' => $buyerNickname,
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
            'valor_produtos' => $valorProdutos,
            'taxas' => $taxas,
            'taxa_platform' => floatval($order->valor_taxa_platform ?? 0),
            'taxa_pagamento' => floatval($order->valor_taxa_pagamento ?? 0),
            'valor_imposto' => round($valorImposto, 2),
            'aliquota_imposto' => $empresa ? floatval($empresa->aliquota_simples ?? 0) : 0,
            'valor_liquido' => $valorLiquido,
            'custo_total' => $custoTotal,
            'lucro' => $lucroValor,
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
            'order_json' => $order->order_json,
            'cart_json' => $order->cart_json,
            'payments_json' => $order->payments_json,
            'shipments_json' => $order->shipments_json,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }

    private function hasNfe($order)
    {
        $pedidoId = $order->pedido_id;

        if (! $pedidoId) {
            return false;
        }

        $nfe = $order->nfeEmitida;

        return $nfe ? [
            'numero' => $nfe->numero,
            'chave' => $nfe->chave,
            'status' => $nfe->status,
        ] : null;
    }

    public function show($id)
    {
        $order = MarketplacePedido::findOrFail($id);
        $empresa = Empresa::find($order->empresa_id);
        
        // Use the proper formatted output rather than raw response
        return response()->json($this->formatOrder($order, null, $empresa));
    }

    public function linkItem(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'item_id' => 'required|string',
            'produto_id' => 'required|exists:products,id'
        ]);

        $order = MarketplacePedido::findOrFail($request->order_id);
        
        // Find the seller sku for this item to fallback on
        $jsonData = $order->json_data ?? [];
        $orderItems = $jsonData['order_items'] ?? $order->cart_json ?? [];
        $sellerSku = null;
        
        foreach ($orderItems as $item) {
            $itemId = $item['item']['id'] ?? $item['OrderItemId'] ?? null;
            if ($itemId === $request->item_id) {
                $sellerSku = $item['item']['seller_sku'] ?? $item['SellerSKU'] ?? null;
                break;
            }
        }
        
        $anuncioQuery = \App\Models\MarketplaceAnuncio::where('empresa_id', $order->empresa_id)
            ->where(function($q) use ($request, $sellerSku) {
                $q->where('external_id', $request->item_id);
                if ($sellerSku) {
                    $q->orWhere('sku', $sellerSku);
                }
            });
            
        if ($order->marketplace) {
            $anuncioQuery->where('marketplace', $order->marketplace);
        }
            
        $anuncio = $anuncioQuery->first();

        if ($anuncio) {
            $anuncio->update(['product_sku_id' => $request->produto_id]);
        } else {
            return response()->json(['error' => 'Anúncio correspondente não encontrado no banco de dados para vincular.'], 404);
        }

        // Optional: Update the sku in the order's json_data as well if needed
        $productSku = \App\Models\ProductSku::where('product_id', $request->produto_id)->first();
        if ($productSku && isset($jsonData['order_items'])) {
            foreach ($jsonData['order_items'] as &$mItem) {
                $itemId = $mItem['item']['id'] ?? $mItem['OrderItemId'] ?? null;
                if ($itemId === $request->item_id) {
                    $mItem['item']['seller_sku'] = $productSku->sku;
                }
            }
            $order->update(['json_data' => $jsonData]);
        }

        $empresa = Empresa::find($order->empresa_id);

        return response()->json([
            'success' => true,
            'message' => 'Produto vinculado com sucesso ao item do pedido.',
            'order' => $this->formatOrder($order, null, $empresa)
        ]);
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

    public function recalculate(Request $request)
    {
        $empresaId = $request->get('empresa_id', session('empresa_id', 6));
        if (! $empresaId) {
            return response()->json(['error' => 'Empresa não definida'], 422);
        }

        $empresa = Empresa::find($empresaId);
        if (! $empresa) {
            return response()->json(['error' => 'Empresa não encontrada'], 404);
        }

        $query = MarketplacePedido::where('empresa_id', $empresaId);

        if ($request->filled('id')) {
            $query->where('id', $request->get('id'));
        } elseif ($request->filled('order_id')) {
            $query->where('pedido_id', $request->get('order_id'));
        } else {
            $fromStr = $request->get('from');
            $toStr = $request->get('to');
            
            if (!$fromStr || !$toStr) {
                return response()->json(['error' => 'Período não definido (de/até)'], 422);
            }

            $from = Carbon::parse($fromStr)->startOfDay();
            $to = Carbon::parse($toStr)->endOfDay();

            if ($from->diffInDays($to) > 31) {
                return response()->json(['error' => 'O período máximo para recálculo é de 31 dias.'], 422);
            }

            $query->whereBetween('data_compra', [$from, $to]);
        }

        $orders = $query->get();
        $count = 0;
        $formattedOrders = [];
        
        // Cache service instances for efficiency
        $meliService = new MeliIntegrationService($empresaId);
        $amazonService = new AmazonSpApiService($empresaId);

        foreach ($orders as $order) {
            if ($order->marketplace === 'mercadolivre') {
                $meliService->recalculateOrderFinancials($order);
            } elseif ($order->marketplace === 'amazon') {
                $amazonService->refreshOrder($order->id);
            } else {
                $this->profitService->persistFinancialFields($order, $empresa);
            }
            
            $count++;
            if ($orders->count() <= 5) {
                $formattedOrders[] = $this->formatOrder($order, null, $empresa);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $count . ' pedido(s) recalculado(s) com sucesso usando dados locais.',
            'recalculated' => $count,
            'orders' => $formattedOrders,
        ]);
    }

    public function integrations(Request $request)
    {
        $empresaId = (int) $request->get('empresa_id', session('empresa_id', 6));

        $allIntegrations = Integracao::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->whereIn('marketplace', ['mercadolivre', 'amazon'])
            ->get(['id', 'marketplace', 'nome_conta', 'external_user_id']);

        $meliIntegrations = $allIntegrations->where('marketplace', 'mercadolivre')
            ->map(fn ($i) => [
                'id' => $i->id,
                'marketplace' => $i->marketplace,
                'nome_conta' => $i->nome_conta,
                'user_id' => $i->external_user_id,
            ])->values();

        $amazonIntegrations = $allIntegrations->where('marketplace', 'amazon')
            ->map(fn ($i) => [
                'id' => $i->id,
                'marketplace' => $i->marketplace,
                'nome_conta' => $i->nome_conta,
                'user_id' => $i->external_user_id,
            ])->values();

        return response()->json([
            'mercadolivre' => $meliIntegrations,
            'amazon' => $amazonIntegrations,
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

                $from = $request->get('data_de');
                $to = $request->get('data_ate');
                $page = $request->get('page');
                
                if ($from && $to) {
                    $fromDate = Carbon::parse($from);
                    $toDate = Carbon::parse($to);
                    if ($fromDate->diffInDays($toDate) > 31) {
                        return response()->json([
                            'success' => false,
                            'message' => 'O período máximo para sincronização é de 31 dias.',
                        ], 422);
                    }
                }

                $result = $service->syncOrders($from, $to, $page !== null ? (int)$page : null);

                return response()->json($result);
            }

            if ($marketplace === 'amazon') {
                $service = new AmazonSpApiService($empresaId);

                $from = $request->get('data_de');
                $to = $request->get('data_ate');
                $nextToken = $request->get('next_token'); // Amazon usa NextToken em vez de Offset

                $result = $service->syncOrders($from, $to, $nextToken);

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

            if ($pedido->marketplace === 'mercadolivre') {
                $service = new MeliIntegrationService($empresaId);
                $result = $service->refreshOrder($id);
            } elseif ($pedido->marketplace === 'amazon') {
                $service = new AmazonSpApiService($empresaId);
                $result = $service->refreshOrder($id);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Atualização disponível apenas para Mercado Livre e Amazon',
                ], 400);
            }

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
