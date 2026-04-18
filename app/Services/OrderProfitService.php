<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\MarketplaceAnuncio;
use App\Models\MarketplacePedido;
use App\Models\ProductComponent;
use App\Models\ProductSku;
use Illuminate\Support\Collection;

class OrderProfitService
{
    /**
     * Return the unique SKUs that appear across all provided orders.
     */
    public function extractSkus(Collection $orders): array
    {
        $skus = [];

        foreach ($orders as $order) {
            $json = $order->json_data ?? [];
            $items = $json['order_items'] ?? [];

            foreach ($items as $item) {
                $sku = $item['item']['seller_sku'] ?? null;
                if ($sku) {
                    $skus[] = $sku;
                }
            }
        }

        return array_values(array_unique(array_filter($skus)));
    }

    /**
     * Build a detailed profit summary for a single order.
     */
    public function calculateOrderProfit(MarketplacePedido $order, ?Empresa $empresa = null, ?Collection $skusInfo = null): array
    {
        $itens = $this->getOrderItems($order, $skusInfo);
        $custoTotal = array_sum(array_column($itens, 'custo_total'));
        
        $valorProdutos = floatval($order->valor_produtos ?? 0);
        $valorFrete = floatval($order->valor_frete ?? 0);
        $taxaPlatform = floatval($order->valor_taxa_platform ?? 0);
        $taxaPagamento = floatval($order->valor_taxa_pagamento ?? 0);
        $valorImposto = floatval($order->valor_imposto ?? 0);
        $valorOutros = floatval($order->valor_outros ?? 0);

        // Se nao tem imposto gravado, tentamos estimar baseado na aliquota da empresa para o lucro
        $impostoCalculado = 0;
        if ($valorImposto <= 0 && $empresa && floatval($empresa->aliquota_simples) > 0) {
            $impostoCalculado = $valorProdutos * (floatval($empresa->aliquota_simples) / 100);
        }

        $impostoFinal = ($valorImposto > 0) ? $valorImposto : $impostoCalculado;

        // Valor Liquido Real (o que a plataforma nos paga de fato, ANTES do custo do produto e impostos locais internos)
        // No Mercado Livre: Liquido = Produtos - Taxas - Frete pago pelo vendedor
        $valorLiquidoVenda = $valorProdutos - $taxaPlatform - $taxaPagamento - $valorOutros - $valorFrete;

        // Lucro Final = Liquido de Venda - Custo dos Produtos - Imposto local
        $lucroValor = $valorLiquidoVenda - $custoTotal - $impostoFinal;

        // Se o pedido está cancelado, o lucro real é zero (pois o item volta ao estoque e o dinheiro volta ao cliente)
        if ($order->status === 'cancelado') {
            $lucroValor = 0;
            $custoTotal = 0;
            $impostoFinal = 0;
            $valorLiquidoVenda = 0;
        }
        
        $lucroPercent = $valorProdutos > 0 ? round(($lucroValor / $valorProdutos) * 100, 1) : 0;

        return [
            'itens' => $itens,
            'lucro_valor' => round($lucroValor, 2),
            'lucro_percent' => $lucroPercent,
            'valor_liquido' => round($valorLiquidoVenda, 2), // Representa o payout esperado do canal (sem impostos locais)
            'valor_frete' => round($valorFrete, 2),
            'custo_total' => round($custoTotal, 2),
            'valor_imposto' => round($impostoFinal, 2),
        ];
    }

    /**
     * Resolve the item-level data (including cost) for a single order.
     */
    public function getOrderItems(MarketplacePedido $order, ?Collection $skusInfo = null): array
    {
        $jsonData = $order->json_data ?? [];
        $orderItems = $jsonData['order_items'] ?? $order->cart_json ?? [];
        $itens = [];

        foreach ($orderItems as $item) {
            // Mapping for Amazon vs Mercado Livre
            $sku = $item['item']['seller_sku'] ?? $item['SellerSKU'] ?? null;
            $itemId = $item['item']['id'] ?? $item['OrderItemId'] ?? null;
            $titulo = $item['item']['title'] ?? $item['Title'] ?? 'Produto sem título';
            $quantidade = $item['quantity'] ?? $item['QuantityOrdered'] ?? 1;
            $precoUnitario = floatval($item['unit_price'] ?? ($item['ItemPrice']['Amount'] ?? 0) / (max(1, $quantidade)));
            $precoTotal = $precoUnitario * $quantidade;
            $thumbnail = $item['item']['thumbnail'] ?? $item['item']['picture'] ?? null;

            $custoUnitario = 0;
            $isKit = false;
            $kitComponents = [];
            $isLinked = false;
            $anuncioId = null;
            $produtoId = null;

            $productSku = null;

            if ($sku) {
                $productSku = $skusInfo && $skusInfo->has($sku)
                    ? $skusInfo->get($sku)
                    : ProductSku::with('product')->where('sku', $sku)->first();

                if ($productSku) {
                    $isLinked = true;
                    $produtoId = $productSku->product_id;

                    if ($productSku->product && $productSku->product->tipo === 'composto') {
                        $isKit = true;
                        $components = ProductComponent::with('componentProduct')
                            ->where('product_id', $productSku->product->id)
                            ->get();

                        foreach ($components as $component) {
                            $componentSku = ProductSku::where('product_id', $component->component_product_id)->first();
                            $componentCost = $componentSku ? floatval($componentSku->preco_custo) : 0;
                            $custoUnitario += $componentCost * $component->quantity;
                            $kitComponents[] = [
                                'nome' => $component->componentProduct->nome ?? 'Componente',
                                'sku' => $componentSku->sku ?? $component->componentProduct->sku ?? '',
                                'quantidade' => $component->quantity * $quantidade,
                            ];
                        }
                    } else {
                        $custoUnitario = floatval($productSku->preco_custo);
                        if ($custoUnitario <= 0 && $productSku->product) {
                            $custoUnitario = floatval($productSku->product->preco_custo);
                        }
                    }
                }
            }

            if (!$isLinked && $itemId) {
                $anuncio = MarketplaceAnuncio::where('external_id', $itemId)->first();
                if ($anuncio) {
                    $anuncioId = $anuncio->id;
                    if ($anuncio->product_sku_id) {
                        $isLinked = true;
                        $productSku = ProductSku::with('product')->find($anuncio->product_sku_id);
                        $produtoId = $productSku->product_id ?? null;

                        if ($productSku) {
                            $custoUnitario = floatval($productSku->preco_custo);
                            if ($custoUnitario <= 0 && $productSku->product) {
                                $custoUnitario = floatval($productSku->product->preco_custo);
                            }
                        }
                    }
                }
            }

            $itens[] = [
                'sku' => $sku,
                'item_id' => $itemId,
                'anuncio_id' => $anuncioId,
                'produto_id' => $produtoId,
                'is_linked' => $isLinked,
                'titulo' => $titulo,
                'titulo_reduzido' => mb_strimwidth($titulo, 0, 35, '...'),
                'quantidade' => $quantidade,
                'preco_unitario' => $precoUnitario,
                'preco_total' => $precoTotal,
                'custo_unitario' => round($custoUnitario, 2),
                'custo_total' => round($custoUnitario * $quantidade, 2),
                'thumbnail' => $thumbnail,
                'is_kit' => $isKit,
                'kit_components' => $kitComponents,
            ];
        }

        return $itens;
    }

    public function persistFinancialFields(MarketplacePedido $order, ?Empresa $empresa = null, ?Collection $skusInfo = null): MarketplacePedido
    {
        $profit = $this->calculateOrderProfit($order, $empresa, $skusInfo);
        $order->update([
            'valor_liquido' => $profit['valor_liquido'],
            'lucro' => $profit['lucro_valor'],
            'custo_total' => $profit['custo_total'],
        ]);

        return $order;
    }

}
