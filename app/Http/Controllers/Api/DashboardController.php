<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\MarketplacePedido;
use App\Models\ProductSku;
use App\Services\OrderProfitService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private OrderProfitService $profitService;

    public function __construct()
    {
        $this->profitService = new OrderProfitService();
    }

    public function lucratividade(Request $request)
    {
        $user = $request->user();
        $empresaIds = $this->resolveEmpresaIds($user);

        if (empty($empresaIds)) {
            return response()->json([
                'day' => $this->emptyStats(),
                'month' => $this->emptyStats(),
                'last_updated' => now()->toIso8601String(),
            ]);
        }

        $empresaId = $request->get('empresa_id', $user->current_empresa_id ?? $empresaIds[0]);
        $empresa = Empresa::find($empresaId);

        $dayOrders = $this->getOrdersForPeriod($empresaIds, Carbon::now()->startOfDay(), Carbon::now()->endOfDay());
        $monthOrders = $this->getOrdersForPeriod($empresaIds, Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $allOrders = $dayOrders->merge($monthOrders);
        $skus = $this->profitService->extractSkus($allOrders);
        $skusInfo = $skus
            ? ProductSku::with('product')->whereIn('sku', $skus)->get()->keyBy('sku')
            : collect();

        return response()->json([
            'day' => $this->buildAggregatedStats($dayOrders, $empresa, $skusInfo),
            'month' => $this->buildAggregatedStats($monthOrders, $empresa, $skusInfo),
            'last_updated' => now()->toIso8601String(),
        ]);
    }

    private function getOrdersForPeriod(array $empresaIds, Carbon $start, Carbon $end): Collection
    {
        return MarketplacePedido::whereIn('empresa_id', $empresaIds)
            ->whereBetween('data_compra', [$start, $end])
            ->orderBy('data_compra', 'desc')
            ->get();
    }

    private function buildAggregatedStats(Collection $orders, ?Empresa $empresa, Collection $skusInfo): array
    {
        $lucro = 0;
        $vendas = 0;
        $valorLiquidoTotal = 0;

        foreach ($orders as $order) {
            $result = $this->profitService->calculateOrderProfit($order, $empresa, $skusInfo);
            $lucro += $result['lucro_valor'];
            $valorLiquidoTotal += max($result['valor_liquido'], 0);
            $vendas += floatval($order->valor_total ?? 0);
        }

        $margem = $valorLiquidoTotal > 0 ? round(($lucro / $valorLiquidoTotal) * 100, 1) : 0;

        return [
            'lucro' => round($lucro, 2),
            'vendas' => round($vendas, 2),
            'pedidos' => $orders->count(),
            'margem' => $margem,
            'last_order' => $orders->first()?->data_compra?->toIso8601String(),
        ];
    }

    private function resolveEmpresaIds($user): array
    {
        $ids = [];

        if ($user->grupo_id) {
            $ids = Empresa::where('grupo_id', $user->grupo_id)->pluck('id')->toArray();
        }

        if (empty($ids)) {
            $ids = $user->empresas()->pluck('id')->toArray();
        }

        if (empty($ids)) {
            $current = $user->current_empresa_id ?? session('empresa_id', 6);
            if ($current) {
                $ids = [$current];
            }
        }

        return array_values(array_filter(array_unique($ids)));
    }

    private function emptyStats(): array
    {
        return [
            'lucro' => 0,
            'vendas' => 0,
            'pedidos' => 0,
            'margem' => 0,
            'last_order' => null,
        ];
    }

    /**
     * Vendas diárias (últimos N dias) — para gráfico de área/linha
     */
    public function vendasDiarias(Request $request)
    {
        $user = $request->user();
        $empresaIds = $this->resolveEmpresaIds($user);
        $days = min((int) $request->get('days', 14), 90);

        if (empty($empresaIds)) {
            return response()->json(['data' => []]);
        }

        $dados = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $data = Carbon::now()->subDays($i)->startOfDay();
            $dataFim = Carbon::now()->subDays($i)->endOfDay();

            $query = MarketplacePedido::whereIn('empresa_id', $empresaIds)
                ->whereBetween('data_compra', [$data, $dataFim]);

            $vendas = (clone $query)->sum('valor_total');
            $pedidos = (clone $query)->count();
            $frete = (clone $query)->sum('valor_frete');

            $dados[] = [
                'dia' => $data->format('d/m'),
                'data' => $data->format('Y-m-d'),
                'vendas' => round((float) $vendas, 2),
                'pedidos' => $pedidos,
                'frete' => round((float) $frete, 2),
            ];
        }

        return response()->json(['data' => $dados]);
    }

    /**
     * Vendas por Marketplace (mês atual) — para gráfico donut
     */
    public function vendasPorMarketplace(Request $request)
    {
        $user = $request->user();
        $empresaIds = $this->resolveEmpresaIds($user);

        if (empty($empresaIds)) {
            return response()->json(['data' => []]);
        }

        $inicio = Carbon::now()->startOfMonth();
        $fim = Carbon::now()->endOfDay();

        $vendas = MarketplacePedido::whereIn('empresa_id', $empresaIds)
            ->whereBetween('data_compra', [$inicio, $fim])
            ->selectRaw("COALESCE(marketplace, 'outros') as marketplace, SUM(valor_total) as total, COUNT(*) as quantidade")
            ->groupBy('marketplace')
            ->orderByDesc('total')
            ->get();

        $cores = [
            'mercadolivre' => '#FBBF24',
            'shopee' => '#F97316',
            'amazon' => '#3B82F6',
            'bling' => '#10B981',
            'magalu' => '#EF4444',
            'outros' => '#6B7280',
        ];

        $nomes = [
            'mercadolivre' => 'Mercado Livre',
            'shopee' => 'Shopee',
            'amazon' => 'Amazon',
            'bling' => 'Bling',
            'magalu' => 'Magalu',
            'outros' => 'Outros',
        ];

        $data = $vendas->map(function ($v) use ($cores, $nomes) {
            $key = $v->marketplace ?? 'outros';
            return [
                'marketplace' => $key,
                'nome' => $nomes[$key] ?? ucfirst($key),
                'total' => round((float) $v->total, 2),
                'quantidade' => (int) $v->quantidade,
                'cor' => $cores[$key] ?? '#6B7280',
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Atividade por Hora (hoje) — para gráfico de barras
     */
    public function atividadeHoraria(Request $request)
    {
        $user = $request->user();
        $empresaIds = $this->resolveEmpresaIds($user);

        if (empty($empresaIds)) {
            return response()->json(['data' => []]);
        }

        $hoje = Carbon::now()->startOfDay();
        $agora = Carbon::now();

        $pedidos = MarketplacePedido::whereIn('empresa_id', $empresaIds)
            ->whereBetween('data_compra', [$hoje, $agora])
            ->get();

        $horas = [];
        for ($h = 0; $h <= 23; $h++) {
            $horas[$h] = ['hora' => sprintf('%02d:00', $h), 'pedidos' => 0, 'valor' => 0];
        }

        foreach ($pedidos as $p) {
            if ($p->data_compra) {
                $h = (int) $p->data_compra->format('H');
                $horas[$h]['pedidos']++;
                $horas[$h]['valor'] += (float) $p->valor_total;
            }
        }

        // Retorna só até a hora atual + 1
        $horaAtual = (int) $agora->format('H');
        $dados = array_values(array_slice($horas, 0, $horaAtual + 1));

        return response()->json(['data' => $dados]);
    }

    /**
     * Top Produtos — ranking por lucratividade (mês) — Top 10 High e Top 10 Low
     */
    public function topProdutos(Request $request)
    {
        $user = $request->user();
        $empresaIds = $this->resolveEmpresaIds($user);

        if (empty($empresaIds)) {
            return response()->json(['high' => [], 'low' => []]);
        }

        $empresaId = $request->get('empresa_id', $user->current_empresa_id ?? $empresaIds[0]);
        $empresa = Empresa::find($empresaId);

        $inicio = Carbon::now()->subDays(30)->startOfDay();
        $fim = Carbon::now()->endOfDay();

        $pedidos = MarketplacePedido::whereIn('empresa_id', $empresaIds)
            ->whereBetween('data_compra', [$inicio, $fim])
            ->get();

        $allSkus = $this->profitService->extractSkus($pedidos);
        $skusInfo = !empty($allSkus)
            ? ProductSku::with('product')->whereIn('sku', $allSkus)->get()->keyBy('sku')
            : collect();

        $produtos = [];
        foreach ($pedidos as $pedido) {
            $profit = $this->profitService->calculateOrderProfit($pedido, $empresa, $skusInfo);
            $items = $profit['itens'] ?? [];

            foreach ($items as $item) {
                $titulo = $item['titulo'] ?? 'Produto desconhecido';
                $sku = $item['sku'] ?? '';
                $qty = (int) ($item['quantidade'] ?? 1);
                $receita = (float) ($item['preco_total'] ?? 0);
                $custo = (float) ($item['custo_total'] ?? 0);
                $key = md5($titulo . $sku); // Group by title + sku for better accuracy

                if (!isset($produtos[$key])) {
                    $produtos[$key] = [
                        'titulo' => mb_substr($titulo, 0, 80),
                        'sku' => $sku,
                        'quantidade' => 0,
                        'receita' => 0,
                        'custo' => 0,
                        'lucro' => 0,
                    ];
                }
                $produtos[$key]['quantidade'] += $qty;
                $produtos[$key]['receita'] += $receita;
                $produtos[$key]['custo'] += $custo;
            }
        }

        // Calcular lucro bruto por produto
        $processed = [];
        foreach ($produtos as $p) {
            $p['lucro'] = round($p['receita'] - $p['custo'], 2);
            $p['receita'] = round($p['receita'], 2);
            $p['custo'] = round($p['custo'], 2);
            // Evitar divisao por zero e garantir que margem nao seja bizarra
            $p['margem'] = $p['receita'] > 0 ? round(($p['lucro'] / $p['receita']) * 100, 1) : 0;
            $processed[] = $p;
        }

        // Ordenar por lucro (maior para menor)
        usort($processed, fn($a, $b) => $b['lucro'] <=> $a['lucro']);

        $topHigh = array_slice($processed, 0, 10);
        
        // Inverter para pegar os menores (prejuizos ou menores lucros)
        $bottom = array_reverse($processed);
        $topLow = array_slice($bottom, 0, 10);

        // Porcentagem relativa para as barras
        $maxLucro = abs($topHigh[0]['lucro'] ?? 0.01) ?: 0.01;
        foreach ($topHigh as &$p) {
            $p['percentual'] = round((abs($p['lucro']) / $maxLucro) * 100);
        }

        $maxPrejuizo = abs(end($topLow)['lucro'] ?? $topLow[0]['lucro'] ?? 0.01) ?: 0.01;
        foreach ($topLow as &$p) {
            $p['percentual'] = round((abs($p['lucro']) / $maxPrejuizo) * 100);
        }

        return response()->json([
            'high' => $topHigh,
            'low' => $topLow
        ]);
    }

    /**
     * Pedidos recentes — feed com lucratividade e produto
     */
    public function pedidosRecentes(Request $request)
    {
        $user = $request->user();
        $empresaIds = $this->resolveEmpresaIds($user);
        $limit = min((int) $request->get('limit', 10), 30);

        if (empty($empresaIds)) {
            return response()->json(['data' => []]);
        }

        $empresaId = $request->get('empresa_id', $user->current_empresa_id ?? $empresaIds[0]);
        $empresa = Empresa::find($empresaId);

        $pedidos = MarketplacePedido::whereIn('empresa_id', $empresaIds)
            ->orderBy('data_compra', 'desc')
            ->limit($limit)
            ->get();

        $allSkus = $this->profitService->extractSkus($pedidos);
        $skusInfo = !empty($allSkus)
            ? ProductSku::with('product')->whereIn('sku', $allSkus)->get()->keyBy('sku')
            : collect();

        $data = $pedidos->map(function ($p) use ($empresa, $skusInfo) {
            $profit = $this->profitService->calculateOrderProfit($p, $empresa, $skusInfo);
            $firstItem = $profit['itens'][0] ?? null;

            return [
                'id' => $p->id,
                'pedido_id' => $p->pedido_id,
                'marketplace' => $p->marketplace,
                'status' => $p->status,
                'comprador' => $p->comprador_nome ? mb_substr($p->comprador_nome, 0, 30) : 'Cliente',
                'valor' => round((float) $p->valor_total, 2),
                'data' => $p->data_compra?->toIso8601String(),
                'pagamento' => $p->status_pagamento,
                'envio' => $p->status_envio,
                'lucro' => $profit['lucro_valor'],
                'margem' => $profit['lucro_percent'],
                'produto' => $firstItem ? mb_substr($firstItem['titulo'], 0, 50) : null,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Vendas com lucro negativo — alertas de precificação
     */
    public function vendasNegativas(Request $request)
    {
        $user = $request->user();
        $empresaIds = $this->resolveEmpresaIds($user);

        if (empty($empresaIds)) {
            return response()->json(['data' => [], 'total' => 0, 'prejuizo' => 0]);
        }

        $empresaId = $request->get('empresa_id', $user->current_empresa_id ?? $empresaIds[0]);
        $empresa = Empresa::find($empresaId);

        $inicio = Carbon::now()->startOfMonth();
        $fim = Carbon::now()->endOfDay();

        $pedidos = MarketplacePedido::whereIn('empresa_id', $empresaIds)
            ->whereBetween('data_compra', [$inicio, $fim])
            ->get();

        $allSkus = $this->profitService->extractSkus($pedidos);
        $skusInfo = !empty($allSkus)
            ? ProductSku::with('product')->whereIn('sku', $allSkus)->get()->keyBy('sku')
            : collect();

        $negativos = [];
        foreach ($pedidos as $pedido) {
            $profit = $this->profitService->calculateOrderProfit($pedido, $empresa, $skusInfo);
            if ($profit['lucro_valor'] < 0) {
                $firstItem = $profit['itens'][0] ?? null;
                $negativos[] = [
                    'pedido_id' => $pedido->pedido_id,
                    'marketplace' => $pedido->marketplace,
                    'valor' => round((float) $pedido->valor_total, 2),
                    'lucro' => $profit['lucro_valor'],
                    'margem' => $profit['lucro_percent'],
                    'produto' => $firstItem ? mb_substr($firstItem['titulo'], 0, 60) : 'Desconhecido',
                    'data' => $pedido->data_compra?->toIso8601String(),
                ];
            }
        }

        // Ordenar pelo maior prejuízo
        usort($negativos, fn($a, $b) => $a['lucro'] <=> $b['lucro']);

        $prejuizoTotal = round(array_sum(array_column($negativos, 'lucro')), 2);

        return response()->json([
            'data' => array_slice($negativos, 0, 10),
            'total' => count($negativos),
            'prejuizo' => $prejuizoTotal,
        ]);
    }
}
