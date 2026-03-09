@extends('layouts.alpine')

@section('header_title', 'Dashboard')
@section('content')
<div x-data="dashboard()" x-init="init()" class="space-y-8 pb-12">

    <!-- HEADER PREMIUM -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/20 via-purple-600/10 to-transparent rounded-3xl"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-6 p-8 bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl">
            <div>
                <h2 class="text-3xl font-black text-white tracking-tight italic uppercase flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <i class="fas fa-bolt text-white text-xl"></i>
                    </div>
                    Painel Executivo
                </h2>
                <p class="text-slate-500 text-sm font-bold mt-2 italic">
                    Visão geral das suas operações em tempo real
                </p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-[10px] font-black text-slate-400 bg-black/40 border border-slate-800 px-4 py-2.5 rounded-2xl uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live · <span x-text="formatLastUpdate()">--:--</span>
                </div>
                <button @click="refreshAll()" class="w-10 h-10 flex items-center justify-center bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-xl border border-slate-700 transition-all active:scale-95">
                    <i class="fas fa-sync-alt text-xs" :class="{'animate-spin': refreshing}"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- KPI HERO: LUCRO DIA + MÊS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-cloak>
        <div class="relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 via-emerald-500/5 to-transparent rounded-3xl transition-all group-hover:from-emerald-500/30"></div>
            <div class="relative bg-slate-900/60 backdrop-blur-xl border border-emerald-500/30 rounded-3xl p-8 shadow-xl shadow-emerald-900/20">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-emerald-400">Lucro do Dia</p>
                        <h3 class="text-4xl font-black text-white mt-3 italic tracking-tighter" x-text="formatMoney(stats.day.lucro)">R$ 0,00</h3>
                        <div class="flex items-center gap-3 mt-3">
                            <span class="text-[10px] font-black bg-emerald-500/20 text-emerald-400 px-3 py-1 rounded-lg border border-emerald-500/30 uppercase" x-text="stats.day.pedidos + ' pedidos'">0 pedidos</span>
                            <span class="text-[10px] font-black text-slate-500 uppercase" x-text="'Vendas: ' + formatMoney(stats.day.vendas)">Vendas: R$ 0,00</span>
                        </div>
                    </div>
                    <div class="text-right space-y-2">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20">
                            <i class="fas fa-trending-up text-2xl"></i>
                        </div>
                        <span class="block text-[10px] font-black uppercase tracking-widest" :class="stats.day.margem >= 0 ? 'text-emerald-400' : 'text-rose-400'" x-text="stats.day.margem + '% margem'">0% margem</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-transparent rounded-3xl transition-all group-hover:from-indigo-500/20"></div>
            <div class="relative bg-slate-900/60 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-400">Lucro do Mês (Grupo)</p>
                        <h3 class="text-4xl font-black text-white mt-3 italic tracking-tighter" x-text="formatMoney(stats.month.lucro)">R$ 0,00</h3>
                        <div class="flex items-center gap-3 mt-3">
                            <span class="text-[10px] font-black bg-indigo-500/20 text-indigo-400 px-3 py-1 rounded-lg border border-indigo-500/30 uppercase" x-text="stats.month.pedidos + ' pedidos'">0 pedidos</span>
                            <span class="text-[10px] font-black text-slate-500 uppercase" x-text="'Vendas: ' + formatMoney(stats.month.vendas)">Vendas: R$ 0,00</span>
                        </div>
                    </div>
                    <div class="text-right space-y-2">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                            <i class="fas fa-chart-pie text-2xl"></i>
                        </div>
                        <span class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest" x-text="stats.month.margem ? stats.month.margem + '% margem' : '—'">—</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI STRIP CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl p-5 hover:border-indigo-500/30 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                    <i class="fas fa-shopping-bag text-lg"></i>
                </div>
            </div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Vendas Dia</p>
            <h3 class="text-xl font-black text-white mt-1 italic tracking-tighter" x-text="formatMoney(stats.day.vendas)">R$ 0,00</h3>
        </div>
        <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl p-5 hover:border-purple-500/30 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400 group-hover:bg-purple-500 group-hover:text-white transition-all">
                    <i class="fas fa-box text-lg"></i>
                </div>
            </div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Pedidos Dia</p>
            <h3 class="text-xl font-black text-white mt-1 italic tracking-tighter" x-text="stats.day.pedidos">0</h3>
        </div>
        <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl p-5 hover:border-emerald-500/30 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                    <i class="fas fa-chart-line text-lg"></i>
                </div>
            </div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Vendas Mês</p>
            <h3 class="text-xl font-black text-white mt-1 italic tracking-tighter" x-text="formatMoney(stats.month.vendas)">R$ 0,00</h3>
        </div>
        <div class="bg-slate-900/50 backdrop-blur-sm border border-slate-800 rounded-2xl p-5 hover:border-amber-500/30 transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 group-hover:bg-amber-500 group-hover:text-white transition-all">
                    <i class="fas fa-ticket-alt text-lg"></i>
                </div>
            </div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Ticket Médio</p>
            <h3 class="text-xl font-black text-white mt-1 italic tracking-tighter" x-text="stats.day.pedidos > 0 ? formatMoney(stats.day.vendas / stats.day.pedidos) : 'R$ 0,00'">R$ 0,00</h3>
        </div>
    </div>

    <!-- GRÁFICO: VENDAS DIÁRIAS (Lazy) -->
    <div x-intersect.once="loadVendasDiarias()" class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-xl">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                    <i class="fas fa-chart-area text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white italic uppercase tracking-tighter">Tendência de Vendas</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Faturamento dos últimos dias</p>
                </div>
            </div>
            <select x-model="chartDays" @change="loadVendasDiarias()" class="bg-black/40 border border-slate-800 rounded-xl px-4 py-2 text-xs font-black text-slate-400 uppercase tracking-wider focus:ring-2 focus:ring-indigo-500/20 outline-none">
                <option value="7">7 dias</option>
                <option value="14" selected>14 dias</option>
                <option value="30">30 dias</option>
            </select>
        </div>
        <div x-show="!vendasDiariasLoaded" class="h-72 flex items-center justify-center">
            <div class="flex flex-col items-center gap-3">
                <div class="w-8 h-8 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Carregando gráfico...</span>
            </div>
        </div>
        <div x-show="vendasDiariasLoaded" x-cloak>
            <div id="chart-vendas-diarias" class="h-72"></div>
        </div>
    </div>

    <!-- ROW: MARKETPLACE DONUT + ATIVIDADE HORÁRIA -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Marketplace Donut (Lazy) -->
        <div x-intersect.once="loadMarketplace()" class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-yellow-500/10 flex items-center justify-center text-yellow-500 border border-yellow-500/20">
                    <i class="fas fa-store text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white italic uppercase tracking-tighter">Marketplace Mix</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Receita por canal este mês</p>
                </div>
            </div>
            <div x-show="!marketplaceLoaded" class="h-64 flex items-center justify-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Carregando...</span>
                </div>
            </div>
            <div x-show="marketplaceLoaded" x-cloak>
                <div id="chart-marketplace" class="h-64"></div>
                <!-- Legenda Custom -->
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <template x-for="mp in marketplaceData" :key="mp.marketplace">
                        <div class="flex items-center gap-2 p-2 bg-black/20 rounded-xl border border-slate-800/50">
                            <div class="w-3 h-3 rounded-full flex-shrink-0" :style="'background:' + mp.cor"></div>
                            <span class="text-[10px] font-black text-slate-400 uppercase truncate" x-text="mp.nome"></span>
                            <span class="text-[10px] font-black text-white ml-auto" x-text="mp.quantidade + ' ped'"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Atividade Horária (Lazy) -->
        <div x-intersect.once="loadAtividadeHoraria()" class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-400 border border-cyan-500/20">
                    <i class="fas fa-clock text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white italic uppercase tracking-tighter">Atividade por Hora</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pedidos registrados hoje</p>
                </div>
            </div>
            <div x-show="!atividadeLoaded" class="h-64 flex items-center justify-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Carregando...</span>
                </div>
            </div>
            <div x-show="atividadeLoaded" x-cloak>
                <div id="chart-atividade" class="h-64"></div>
            </div>
        </div>
    </div>

    <!-- ROW: TOP LUCRATIVIDADE (MAIOR E MENOR) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top 10 MAIOR Lucratividade (Lazy) -->
        <div x-intersect.once="loadTopProdutos()" class="bg-slate-900/50 backdrop-blur-xl border border-emerald-500/10 rounded-3xl p-8 shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20">
                    <i class="fas fa-chart-line text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white italic uppercase tracking-tighter">10+ Lucrativos</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Maiores lucros do mês</p>
                </div>
            </div>
            <div x-show="!topProdutosLoaded" class="space-y-4">
                <template x-for="i in 5" :key="i">
                    <div class="animate-pulse flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-800"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 bg-slate-800 rounded w-3/4"></div>
                            <div class="h-2 bg-slate-800 rounded w-1/2"></div>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="topProdutosLoaded" x-cloak class="space-y-2.5">
                <template x-for="(prod, idx) in topHigh" :key="'high-'+idx">
                    <div class="group p-3 bg-emerald-500/5 border border-emerald-500/10 rounded-2xl hover:bg-emerald-500/10 transition-all">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[10px] font-black bg-emerald-500 text-black"
                                 x-text="'#' + (idx + 1)"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-black text-white truncate group-hover:text-emerald-400 transition-colors" x-text="prod.titulo"></p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[9px] font-bold text-slate-600 uppercase" x-text="prod.sku ? 'SKU: ' + prod.sku : ''"></span>
                                    <span class="text-[9px] font-bold text-slate-600">·</span>
                                    <span class="text-[9px] font-bold text-slate-500" x-text="prod.quantidade + ' un'"></span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-xs font-black text-emerald-400 italic" x-text="formatMoney(prod.lucro)"></p>
                                <p class="text-[9px] font-bold text-emerald-600" x-text="prod.margem + '% margem'"></p>
                                <p class="text-[8px] font-bold text-slate-600" x-text="'Rec: ' + formatMoney(prod.receita)"></p>
                            </div>
                        </div>
                        <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 rounded-full transition-all duration-700" :style="'width:' + prod.percentual + '%'"></div>
                        </div>
                    </div>
                </template>
                <div x-show="topHigh.length === 0" class="py-8 text-center">
                    <i class="fas fa-box-open text-3xl text-slate-700 mb-3 block"></i>
                    <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Sem dados de lucro positivo</p>
                </div>
            </div>
        </div>

        <!-- Top 10 MENOR Lucratividade (Lazy) -->
        <div x-intersect.once="loadTopProdutos()" class="bg-slate-900/50 backdrop-blur-xl border border-rose-500/10 rounded-3xl p-8 shadow-xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400 border border-rose-500/20">
                    <i class="fas fa-chart-area text-lg transform rotate-180"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white italic uppercase tracking-tighter">10+ Críticos</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Menores lucros/prejuízos do mês</p>
                </div>
            </div>
            <div x-show="!topProdutosLoaded" class="space-y-4">
                <template x-for="i in 5" :key="i">
                    <div class="animate-pulse flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-800"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 bg-slate-800 rounded w-3/4"></div>
                            <div class="h-2 bg-slate-800 rounded w-1/2"></div>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="topProdutosLoaded" x-cloak class="space-y-2.5">
                <template x-for="(prod, idx) in topLow" :key="'low-'+idx">
                    <div class="group p-3 bg-rose-500/5 border border-rose-500/10 rounded-2xl hover:bg-rose-500/10 transition-all">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[10px] font-black"
                                 :class="prod.lucro < 0 ? 'bg-rose-500 text-black' : 'bg-slate-700 text-slate-300'"
                                 x-text="'#' + (idx + 1)"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-black text-white truncate group-hover:text-rose-400 transition-colors" x-text="prod.titulo"></p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[9px] font-bold text-slate-600 uppercase" x-text="prod.sku ? 'SKU: ' + prod.sku : ''"></span>
                                    <span class="text-[9px] font-bold text-slate-600">·</span>
                                    <span class="text-[9px] font-bold text-slate-500" x-text="prod.quantidade + ' un'"></span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-xs font-black italic" :class="prod.lucro < 0 ? 'text-rose-400' : 'text-slate-400'" x-text="formatMoney(prod.lucro)"></p>
                                <p class="text-[9px] font-bold" :class="prod.lucro < 0 ? 'text-rose-600' : 'text-slate-500'" x-text="prod.margem + '% margem'"></p>
                                <p class="text-[8px] font-bold text-slate-600" x-text="'Rec: ' + formatMoney(prod.receita)"></p>
                            </div>
                        </div>
                        <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700" :class="prod.lucro < 0 ? 'bg-rose-500' : 'bg-slate-600'" :style="'width:' + prod.percentual + '%'"></div>
                        </div>
                    </div>
                </template>
                <div x-show="topLow.length === 0" class="py-8 text-center">
                    <i class="fas fa-box-open text-3xl text-slate-700 mb-3 block"></i>
                    <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Sem dados de lucro/prejuízo</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW: PEDIDOS RECENTES -->
    <div x-intersect.once="loadPedidosRecentes()" class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
        <div class="p-8 pb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400 border border-purple-500/20">
                    <i class="fas fa-receipt text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white italic uppercase tracking-tighter">Feed de Pedidos</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Monitoramento em tempo real</p>
                </div>
            </div>
            <a href="/orders" class="text-[10px] font-black text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 px-4 py-2 rounded-xl border border-indigo-500/20 uppercase tracking-widest transition-colors">
                Gerenciar Pedidos
            </a>
        </div>
        <div x-show="!pedidosRecentesLoaded" class="p-8 pt-4 space-y-3">
            <template x-for="i in 5" :key="i">
                <div class="animate-pulse flex items-center gap-4 p-4 bg-black/20 rounded-2xl">
                    <div class="w-10 h-10 rounded-xl bg-slate-800"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 bg-slate-800 rounded w-1/3"></div>
                        <div class="h-2 bg-slate-800 rounded w-1/4"></div>
                    </div>
                </div>
            </template>
        </div>
        <div x-show="pedidosRecentesLoaded" x-cloak class="max-h-[600px] overflow-y-auto no-scrollbar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-px bg-slate-800/20">
                <template x-for="order in pedidosRecentes" :key="order.id">
                    <div class="px-8 py-5 bg-slate-900/30 border-b border-slate-800/30 hover:bg-yellow-500/5 transition-all group">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center border transition-colors"
                                     :class="order.marketplace === 'mercadolivre' ? 'bg-yellow-500/10 border-yellow-500/20 text-yellow-500' : order.marketplace === 'amazon' ? 'bg-blue-500/10 border-blue-500/20 text-blue-500' : 'bg-slate-800 border-slate-700 text-slate-400'">
                                    <i :class="getMarketplaceIcon(order.marketplace)" class="text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-white group-hover:text-yellow-500 transition-colors" x-text="'#' + order.pedido_id"></p>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase" x-text="order.comprador + ' · ' + formatDate(order.data)"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-sm font-black text-white italic tracking-tighter" x-text="formatMoney(order.valor)"></p>
                                    <p class="text-[9px] font-black italic" :class="order.lucro >= 0 ? 'text-emerald-400' : 'text-rose-400'" x-text="'Lucro: ' + formatMoney(order.lucro)"></p>
                                </div>
                                <span class="text-[9px] font-black px-2 py-1 rounded-lg" :class="order.lucro >= 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'" x-text="order.margem + '%'"></span>
                            </div>
                        </div>
                        <div x-show="order.produto" class="mt-2 ml-14 text-[10px] font-bold text-slate-600 truncate">
                            <i class="fas fa-box text-slate-700 mr-1"></i> <span x-text="order.produto"></span>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="pedidosRecentes.length === 0" class="p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-full bg-slate-800/30 flex items-center justify-center mb-4">
                    <i class="fas fa-shopping-cart text-3xl text-slate-700"></i>
                </div>
                <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Nenhum pedido recente</p>
            </div>
        </div>
    </div>

    <!-- VENDAS NEGATIVAS (Lazy) -->
    <div x-intersect.once="loadVendasNegativas()" class="bg-slate-900/50 backdrop-blur-xl border border-rose-500/20 rounded-3xl overflow-hidden shadow-xl shadow-rose-900/10">
        <div class="p-8 pb-4 flex items-center justify-between bg-gradient-to-r from-rose-500/10 to-transparent">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400 border border-rose-500/20">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white italic uppercase tracking-tighter">Alertas de Precificação</h3>
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Vendas com lucro negativo neste mês</p>
                </div>
            </div>
            <div x-show="vendasNegativasLoaded" class="flex items-center gap-3">
                <span class="text-[10px] font-black text-rose-400 bg-rose-500/10 px-3 py-1.5 rounded-xl border border-rose-500/20 uppercase" x-text="vendasNegativasTotal + ' pedidos no prejuízo'"></span>
                <span class="text-sm font-black text-rose-400 italic" x-text="formatMoney(vendasNegativasPrejuizo)"></span>
            </div>
        </div>
        <div x-show="!vendasNegativasLoaded" class="p-8 pt-4">
            <div class="animate-pulse space-y-3">
                <div class="h-16 bg-slate-800/50 rounded-2xl"></div>
                <div class="h-16 bg-slate-800/50 rounded-2xl"></div>
                <div class="h-16 bg-slate-800/50 rounded-2xl"></div>
            </div>
        </div>
        <div x-show="vendasNegativasLoaded" x-cloak class="p-8 pt-4">
            <div class="space-y-2">
                <template x-for="(neg, idx) in vendasNegativas" :key="idx">
                    <div class="flex items-center justify-between p-4 bg-rose-500/5 border border-rose-500/10 rounded-2xl hover:bg-rose-500/10 transition-all group">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center text-rose-400 flex-shrink-0">
                                <i class="fas fa-arrow-down text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-black text-white truncate group-hover:text-rose-400 transition-colors" x-text="neg.produto"></p>
                                <p class="text-[9px] font-bold text-slate-500 uppercase" x-text="'#' + neg.pedido_id + ' · ' + formatDate(neg.data)"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="text-right">
                                <p class="text-xs font-bold text-slate-400" x-text="'Venda: ' + formatMoney(neg.valor)"></p>
                                <p class="text-xs font-black text-rose-400 italic" x-text="'Prejuízo: ' + formatMoney(neg.lucro)"></p>
                            </div>
                            <span class="text-[9px] font-black text-rose-400 bg-rose-500/20 px-2 py-1 rounded-lg border border-rose-500/30" x-text="neg.margem + '%'"></span>
                        </div>
                    </div>
                </template>
                <div x-show="vendasNegativas.length === 0" class="py-8 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-emerald-500/5 flex items-center justify-center mb-4 border border-emerald-500/10">
                        <i class="fas fa-check-circle text-3xl text-emerald-500"></i>
                    </div>
                    <p class="text-sm font-black text-emerald-400 uppercase tracking-widest">Nenhuma venda negativa!</p>
                    <p class="text-[10px] font-bold text-slate-600 mt-1">Todas as vendas deste mês estão positivas</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
function dashboard() {
    return {
        stats: {
            day: {lucro: 0, vendas: 0, pedidos: 0, margem: 0},
            month: {lucro: 0, vendas: 0, pedidos: 0, margem: 0},
            loading: true,
            lastUpdated: null,
        },
        refreshing: false,

        vendasDiariasLoaded: false,
        marketplaceLoaded: false,
        atividadeLoaded: false,
        topProdutosLoaded: false,
        pedidosRecentesLoaded: false,
        vendasNegativasLoaded: false,

        chartDays: '14',
        marketplaceData: [],
        topHigh: [],
        topLow: [],
        pedidosRecentes: [],
        vendasNegativas: [],
        vendasNegativasTotal: 0,
        vendasNegativasPrejuizo: 0,

        // Chart instances
        _chartVendas: null,
        _chartMarketplace: null,
        _chartAtividade: null,

        init() {
            this.loadDashboardStats();

            window.addEventListener('empresa-changed', () => {
                this.refreshAll();
            });
        },

        async refreshAll() {
            this.refreshing = true;
            await this.loadDashboardStats();
            if (this.vendasDiariasLoaded) await this.loadVendasDiarias();
            if (this.marketplaceLoaded) await this.loadMarketplace();
            if (this.atividadeLoaded) await this.loadAtividadeHoraria();
            if (this.topProdutosLoaded) await this.loadTopProdutos();
            if (this.vendasNegativasLoaded) await this.loadVendasNegativas();
            this.refreshing = false;
        },

        async loadDashboardStats() {
            this.stats.loading = true;
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/dashboard/lucratividade?empresa_id=${empresaId}`);
                if (response.ok) {
                    const payload = await response.json();
                    this.stats.day = payload.day ?? this.stats.day;
                    this.stats.month = payload.month ?? this.stats.month;
                    this.stats.lastUpdated = payload.last_updated ?? null;
                }
            } catch (e) {
                console.error('Erro ao carregar stats:', e);
            } finally {
                this.stats.loading = false;
            }
        },

        async loadVendasDiarias() {
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/dashboard/vendas-diarias?days=${this.chartDays}&empresa_id=${empresaId}`);
                const payload = await response.json();
                const data = payload.data || [];

                this.vendasDiariasLoaded = true;

                this.$nextTick(() => {
                    if (this._chartVendas) this._chartVendas.destroy();

                    const options = {
                        series: [{
                            name: 'Faturamento',
                            data: data.map(d => d.vendas)
                        }, {
                            name: 'Pedidos',
                            data: data.map(d => d.pedidos)
                        }],
                        chart: {
                            type: 'area',
                            height: 280,
                            background: 'transparent',
                            toolbar: { show: false },
                            animations: { enabled: true, easing: 'easeinout', speed: 800 },
                            fontFamily: 'inherit',
                        },
                        colors: ['#818CF8', '#34D399'],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.4,
                                opacityTo: 0.05,
                                stops: [0, 100],
                            }
                        },
                        stroke: { curve: 'smooth', width: 3 },
                        xaxis: {
                            categories: data.map(d => d.dia),
                            labels: { style: { colors: '#64748B', fontSize: '10px', fontWeight: 800 } },
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: [{
                            labels: {
                                style: { colors: '#64748B', fontSize: '10px', fontWeight: 800 },
                                formatter: (v) => 'R$ ' + (v/1000).toFixed(1) + 'k'
                            },
                        }, {
                            opposite: true,
                            labels: {
                                style: { colors: '#64748B', fontSize: '10px', fontWeight: 800 },
                                formatter: (v) => Math.round(v)
                            },
                        }],
                        grid: {
                            borderColor: '#1E293B',
                            strokeDashArray: 4,
                        },
                        tooltip: {
                            theme: 'dark',
                            y: { formatter: (v, { seriesIndex }) => seriesIndex === 0 ? 'R$ ' + v.toFixed(2).replace('.', ',') : v + ' pedidos' }
                        },
                        legend: {
                            labels: { colors: '#94A3B8' },
                            fontWeight: 800,
                            fontSize: '10px',
                        },
                        dataLabels: { enabled: false },
                    };

                    const el = document.querySelector('#chart-vendas-diarias');
                    if (el) {
                        this._chartVendas = new ApexCharts(el, options);
                        this._chartVendas.render();
                    }
                });
            } catch (e) {
                console.error('Erro ao carregar vendas diárias:', e);
                this.vendasDiariasLoaded = true;
            }
        },

        async loadMarketplace() {
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/dashboard/vendas-marketplace?empresa_id=${empresaId}`);
                const payload = await response.json();
                this.marketplaceData = payload.data || [];
                this.marketplaceLoaded = true;

                this.$nextTick(() => {
                    if (this._chartMarketplace) this._chartMarketplace.destroy();
                    if (this.marketplaceData.length === 0) return;

                    const options = {
                        series: this.marketplaceData.map(d => d.total),
                        labels: this.marketplaceData.map(d => d.nome),
                        colors: this.marketplaceData.map(d => d.cor),
                        chart: {
                            type: 'donut',
                            height: 256,
                            background: 'transparent',
                            fontFamily: 'inherit',
                            animations: { enabled: true, easing: 'easeinout', speed: 800 },
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '70%',
                                    labels: {
                                        show: true,
                                        name: { color: '#94A3B8', fontSize: '11px', fontWeight: 800 },
                                        value: {
                                            color: '#FFFFFF',
                                            fontSize: '18px',
                                            fontWeight: 900,
                                            formatter: (v) => 'R$ ' + parseFloat(v).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                                        },
                                        total: {
                                            show: true,
                                            label: 'Total',
                                            color: '#94A3B8',
                                            fontSize: '10px',
                                            fontWeight: 800,
                                            formatter: (w) => 'R$ ' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".")
                                        }
                                    }
                                }
                            }
                        },
                        stroke: { width: 2, colors: ['#0F172A'] },
                        legend: { show: false },
                        dataLabels: { enabled: false },
                        tooltip: {
                            theme: 'dark',
                            y: { formatter: (v) => 'R$ ' + v.toFixed(2).replace('.', ',') }
                        },
                    };

                    const el = document.querySelector('#chart-marketplace');
                    if (el) {
                        this._chartMarketplace = new ApexCharts(el, options);
                        this._chartMarketplace.render();
                    }
                });
            } catch (e) {
                console.error('Erro ao carregar marketplace:', e);
                this.marketplaceLoaded = true;
            }
        },

        async loadAtividadeHoraria() {
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/dashboard/atividade-horaria?empresa_id=${empresaId}`);
                const payload = await response.json();
                const data = payload.data || [];
                this.atividadeLoaded = true;

                this.$nextTick(() => {
                    if (this._chartAtividade) this._chartAtividade.destroy();

                    const options = {
                        series: [{
                            name: 'Pedidos',
                            data: data.map(d => d.pedidos)
                        }],
                        chart: {
                            type: 'bar',
                            height: 256,
                            background: 'transparent',
                            toolbar: { show: false },
                            fontFamily: 'inherit',
                            animations: { enabled: true, easing: 'easeinout', speed: 800 },
                        },
                        colors: ['#22D3EE'],
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                columnWidth: '60%',
                            }
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'dark',
                                type: 'vertical',
                                shadeIntensity: 0.3,
                                opacityFrom: 1,
                                opacityTo: 0.6,
                                stops: [0, 100],
                            }
                        },
                        xaxis: {
                            categories: data.map(d => d.hora),
                            labels: { style: { colors: '#64748B', fontSize: '9px', fontWeight: 800 } },
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#64748B', fontSize: '10px', fontWeight: 800 },
                                formatter: (v) => Math.round(v)
                            },
                        },
                        grid: {
                            borderColor: '#1E293B',
                            strokeDashArray: 4,
                        },
                        tooltip: {
                            theme: 'dark',
                            y: { formatter: (v) => v + ' pedidos' }
                        },
                        dataLabels: { enabled: false },
                    };

                    const el = document.querySelector('#chart-atividade');
                    if (el) {
                        this._chartAtividade = new ApexCharts(el, options);
                        this._chartAtividade.render();
                    }
                });
            } catch (e) {
                console.error('Erro ao carregar atividade:', e);
                this.atividadeLoaded = true;
            }
        },

        async loadTopProdutos() {
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/dashboard/top-produtos?empresa_id=${empresaId}`);
                const payload = await response.json();
                this.topHigh = payload.high || [];
                this.topLow = payload.low || [];
                this.topProdutosLoaded = true;
            } catch (e) {
                console.error('Erro ao carregar top produtos:', e);
                this.topProdutosLoaded = true;
            }
        },

        async loadPedidosRecentes() {
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/dashboard/pedidos-recentes?empresa_id=${empresaId}`);
                const payload = await response.json();
                this.pedidosRecentes = payload.data || [];
                this.pedidosRecentesLoaded = true;
            } catch (e) {
                console.error('Erro ao carregar pedidos recentes:', e);
                this.pedidosRecentesLoaded = true;
            }
        },

        async loadVendasNegativas() {
            try {
                const empresaId = localStorage.getItem('empresa_id') || '4';
                const response = await fetch(`/api/dashboard/vendas-negativas?empresa_id=${empresaId}`);
                const payload = await response.json();
                this.vendasNegativas = payload.data || [];
                this.vendasNegativasTotal = payload.total || 0;
                this.vendasNegativasPrejuizo = payload.prejuizo || 0;
                this.vendasNegativasLoaded = true;
            } catch (e) {
                console.error('Erro ao carregar vendas negativas:', e);
                this.vendasNegativasLoaded = true;
            }
        },

        formatLastUpdate() {
            if (!this.stats.lastUpdated) return '--:--';
            const date = new Date(this.stats.lastUpdated);
            return date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
        },

        getMarketplaceIcon(mp) {
            const icons = {
                'mercadolivre': 'fab fa-shake',
                'amazon': 'fab fa-amazon',
                'shopee': 'fas fa-shopping-bag',
                'bling': 'fas fa-cash-register',
                'magalu': 'fas fa-box',
            };
            return icons[mp] || 'fas fa-store';
        },

        formatDate(date) {
            if (!date) return '';
            const d = new Date(date);
            return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
        },

        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value || 0);
        }
    }
}
</script>
@endsection
