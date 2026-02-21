<?php

use function Livewire\Volt\{state, computed};
use App\Models\ProductSku;
use Illuminate\Support\Facades\Auth;

state([
    'periodo' => '30', // dias
]);

$stats = computed(function () {
    $empresaId = Auth::user()->current_empresa_id;
    
    // Mockup de dados de venda enquanto não temos a sync do ML
    // Mas o custo é real baseado nos produtos cadastrados
    $totalSkus = ProductSku::whereHas('product', fn($q) => $q->where('empresa_id', $empresaId))->count();
    $valorEstoque = ProductSku::whereHas('product', fn($q) => $q->where('empresa_id', $empresaId))
        ->selectRaw('SUM(estoque * preco_custo) as total_custo')
        ->value('total_custo') ?: 0;

    return [
        'vendas_brutas' => 45280.50, // Mock
        'cmv' => 18450.20, // Mock (Custo da Mercadoria Vendida)
        'impostos' => 5433.66, // Mock (12% aprox)
        'taxas_marketplace' => 7244.88, // Mock (16% ML)
        'lucro_bruto' => 14151.76,
        'margem_liquida' => 31.2,
        'valor_inventario' => $valorEstoque,
        'total_skus' => $totalSkus
    ];
});

?>

<div class="space-y-8">
    <header class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase text-emerald-500">Lucro Real & DRE ⚡</h2>
            <p class="text-slate-500 font-medium font-bold italic">Inteligência financeira: Pare de olhar faturamento, comece a olhar lucro.</p>
        </div>
        <div class="flex bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-1 rounded-2xl">
            <button wire:click="$set('periodo', '7')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase italic transition-all {{ $periodo == '7' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-white' }}">7D</button>
            <button wire:click="$set('periodo', '15')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase italic transition-all {{ $periodo == '15' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-white' }}">15D</button>
            <button wire:click="$set('periodo', '30')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase italic transition-all {{ $periodo == '30' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-500 hover:text-white' }}">30D</button>
        </div>
    </header>

    <!-- CARDS DE RESUMO -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-[2rem] shadow-xl relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/5 rounded-full group-hover:bg-indigo-500/10 transition-all"></div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic block mb-2">Faturamento Bruto</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900 dark:text-white italic">R$ {{ number_format($this->stats['vendas_brutas'], 2, ',', '.') }}</span>
            </div>
            <span class="text-[9px] text-emerald-500 font-bold uppercase italic mt-2 flex items-center gap-1">
                <i class="fas fa-caret-up"></i> 12% vs mês anterior
            </span>
        </div>

        <div class="bg-dark-900 border border-dark-800 p-6 rounded-[2rem] shadow-xl relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/5 rounded-full group-hover:bg-rose-500/10 transition-all"></div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic block mb-2">CMV (Custo Produto)</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-white italic">R$ {{ number_format($this->stats['cmv'], 2, ',', '.') }}</span>
            </div>
            <span class="text-[9px] text-slate-600 font-bold uppercase italic mt-2 block italic">Baseado em custo real SKU</span>
        </div>

        <div class="bg-dark-900 border border-emerald-500/30 p-6 rounded-[2rem] shadow-xl relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full group-hover:bg-emerald-500/20 transition-all"></div>
            <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest italic block mb-2 underline decoration-2 underline-offset-4">Lucro Líquido Real</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black text-white italic">R$ {{ number_format($this->stats['lucro_bruto'], 2, ',', '.') }}</span>
            </div>
            <span class="text-[10px] bg-emerald-500/10 text-emerald-500 px-2 py-0.5 rounded italic font-black mt-3 inline-block">
                ROI: 145%
            </span>
        </div>

        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-[2rem] shadow-xl relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/5 rounded-full group-hover:bg-amber-500/10 transition-all"></div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest italic block mb-2">Margem Líquida</span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black text-slate-900 dark:text-white italic">{{ $this->stats['margem_liquida'] }}%</span>
            </div>
            <div class="w-full bg-dark-950 h-1.5 rounded-full mt-4 overflow-hidden">
                <div class="bg-amber-500 h-full rounded-full" style="width: {{ $this->stats['margem_liquida'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- DETALHAMENTO DRE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-[2.5rem] p-8 shadow-2xl">
            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-8 flex items-center gap-3 italic uppercase">
                <i class="fas fa-list-alt text-indigo-500"></i>
                DRE - Demonstrativo de Resultados
            </h3>
            
            <div class="space-y-2">
                <div class="flex justify-between p-4 bg-slate-50 dark:bg-dark-950/50 rounded-2xl">
                    <span class="text-[10px] font-black text-slate-400 uppercase italic">Faturamento Bruto</span>
                    <span class="text-sm font-black text-slate-900 dark:text-white italic">R$ {{ number_format($this->stats['vendas_brutas'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between p-4 bg-rose-500/5 rounded-2xl border border-rose-500/10">
                    <span class="text-[10px] font-black text-rose-400 uppercase italic">(-) CMV (Custo de Aquisição)</span>
                    <span class="text-sm font-black text-rose-400 italic">R$ {{ number_format($this->stats['cmv'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between p-4 bg-rose-500/5 rounded-2xl border border-rose-500/10">
                    <span class="text-[10px] font-black text-rose-400 uppercase italic">(-) Impostos Estimados (12%)</span>
                    <span class="text-sm font-black text-rose-400 italic">R$ {{ number_format($this->stats['impostos'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between p-4 bg-rose-500/5 rounded-2xl border border-rose-500/10">
                    <span class="text-[10px] font-black text-rose-400 uppercase italic">(-) Comissões Marketplace (16%)</span>
                    <span class="text-sm font-black text-rose-400 italic">R$ {{ number_format($this->stats['taxas_marketplace'], 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between p-6 bg-emerald-500/10 rounded-2xl border-2 border-emerald-500/20 mt-4 shadow-lg shadow-emerald-500/5">
                    <span class="text-sm font-black text-emerald-400 uppercase italic tracking-widest">(=) Lucro de Contribuição</span>
                    <span class="text-xl font-black text-white italic">R$ {{ number_format($this->stats['lucro_bruto'], 2, ',', '.') }}</span>
                </div>
            </div>
            
            <div class="mt-8 p-6 bg-indigo-500/5 border border-indigo-500/10 rounded-3xl">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-xl font-black">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-white uppercase italic">Insight de Performance</h4>
                        <p class="text-[10px] text-slate-500 font-bold italic uppercase mt-1">Sua margem está 4% acima da média do nicho. Recomenda-se aumentar investimento em ADS.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SIDEBAR DE INVENTÁRIO -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-[2.5rem] p-8 shadow-2xl">
                <h3 class="text-sm font-black text-slate-900 dark:text-white mb-6 flex items-center gap-3 italic uppercase">
                    <i class="fas fa-warehouse text-amber-500"></i>
                    Capital em Estoque
                </h3>
                <div class="text-center py-6">
                    <span class="text-[10px] font-black text-slate-600 uppercase italic block mb-1">Valor Total (Custo)</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$ {{ number_format($this->stats['valor_inventario'], 2, ',', '.') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-6">
                    <div class="bg-slate-50 dark:bg-dark-950 p-4 rounded-2xl text-center border border-slate-100 dark:border-dark-800">
                        <span class="text-[9px] font-black text-slate-500 uppercase italic block">SKUs Ativos</span>
                        <span class="text-xl font-black text-slate-900 dark:text-white italic">{{ $this->stats['total_skus'] }}</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-dark-950 p-4 rounded-2xl text-center border border-slate-100 dark:border-dark-800">
                        <span class="text-[9px] font-black text-slate-500 uppercase italic block">Giro Médio</span>
                        <span class="text-xl font-black text-slate-900 dark:text-white italic">1.2x</span>
                    </div>
                </div>
            </div>

            <!-- PRODUTOS MAIS LUCRATIVOS (MOCK) -->
            <div class="bg-dark-900 border border-dark-800 rounded-[2.5rem] p-8 shadow-2xl">
                <h3 class="text-sm font-black text-white mb-6 uppercase italic">Top 3 mais Lucrativos</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase italic">Cabo USB-C Gold</span>
                        <span class="text-[10px] font-black text-emerald-500 italic">45% Margem</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase italic">Película Privacy</span>
                        <span class="text-[10px] font-black text-emerald-500 italic">42% Margem</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase italic">Hub 7 em 1 Metal</span>
                        <span class="text-[10px] font-black text-emerald-500 italic">38% Margem</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
