<div class="space-y-8">
    
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase">
                Painel Executivo ⚡
            </h2>
            <p class="text-slate-500 font-medium font-bold italic">
                Bem-vindo de volta{{ $empresa?->nome ? ', ' . $empresa->nome : '' }}. Aqui está o resumo das suas operações hoje.
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-2 rounded-xl">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            ATUALIZADO: {{ now()->format('H:i') }}
        </div>
    </div>

    <!-- KPI GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Vendas Hoje -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-xl hover:border-indigo-500/50 transition-all group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-500 group-hover:scale-110 transition-transform">
                    <i class="fas fa-sack-dollar text-xl"></i>
                </div>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Vendas Hoje</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">R$ {{ number_format($vendasHoje, 2, ',', '.') }}</h3>
        </div>

        <!-- Faturamento Hoje -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-xl hover:border-emerald-500/50 transition-all group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 group-hover:scale-110 transition-transform">
                    <i class="fas fa-receipt text-xl"></i>
                </div>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Faturamento NF-e</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">R$ {{ number_format($faturamentoHoje, 2, ',', '.') }}</h3>
        </div>

        <!-- Pedidos Hoje -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-xl hover:border-amber-500/50 transition-all group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Pedidos Hoje</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($pedidosHoje, 0, ',', '.') }}</h3>
        </div>

        <!-- Ticket Médio -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-2xl shadow-xl hover:border-rose-500/50 transition-all group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-500 group-hover:scale-110 transition-transform">
                    <i class="fas fa-ticket-alt text-xl"></i>
                </div>
            </div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Ticket Médio</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                R$ {{ $pedidosHoje > 0 ? number_format($vendasHoje / $pedidosHoje, 2, ',', '.') : '0,00' }}
            </h3>
        </div>

    </div>

    <!-- Vendas por Marketplace -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">
            <i class="fas fa-store mr-2 text-indigo-500"></i>
            Vendas por Marketplace (Hoje)
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($vendasPorMarketplace as $key => $mp)
                <div class="p-4 bg-slate-50 dark:bg-dark-800 rounded-xl text-center">
                    <div class="text-2xl mb-2">
                        @switch($key)
                            @case('mercadolivre')
                                <i class="fab fa-mercadolibre text-yellow-500"></i>
                                @break
                            @case('shopee')
                                <i class="fab fa-shopify text-orange-500"></i>
                                @break
                            @case('amazon')
                                <i class="fab fa-amazon text-blue-500"></i>
                                @break
                            @case('bling')
                                <i class="fas fa-cash-register text-green-500"></i>
                                @break
                            @case('magalu')
                                <i class="fas fa-box text-red-500"></i>
                                @break
                            @default
                                <i class="fas fa-globe text-slate-400"></i>
                        @endswitch
                    </div>
                    <p class="text-xs font-bold text-slate-500 uppercase">{{ $mp['nome'] }}</p>
                    <p class="text-lg font-black text-slate-900 dark:text-white">R$ {{ number_format($mp['total'], 2, ',', '.') }}</p>
                    <p class="text-xs text-slate-400">{{ $mp['quantidade'] }} pedidos</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Gráfico de Vendas últimos 7 dias -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-3xl">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">
                <i class="fas fa-chart-line mr-2 text-indigo-500"></i>
                Vendas últimos 7 dias
            </h3>
            <div class="h-64 flex items-end gap-2">
                @php
                    $maxTotal = max(array_column($vendasUltimos7Dias, 'total')) ?: 1;
                @endphp
                @foreach($vendasUltimos7Dias as $dado)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-indigo-500 rounded-t-lg hover:bg-indigo-600 transition-all relative group" 
                             style="height: {{ $dado['total'] > 0 ? ($dado['total'] / $maxTotal * 100) : 2 }}%">
                            <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-slate-900 dark:bg-dark-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                R$ {{ number_format($dado['total'], 2, ',', '.') }}
                            </div>
                        </div>
                        <span class="text-xs text-slate-500 mt-2">{{ $dado['dia'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Resumo Lateral -->
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-6 rounded-3xl">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6">
                <i class="fas fa-chart-pie mr-2 text-emerald-500"></i>
                Resumo
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-800 rounded-xl">
                    <span class="text-sm text-slate-600 dark:text-slate-300">Total 7 dias</span>
                    <span class="font-bold text-slate-900 dark:text-white">
                        R$ {{ number_format(array_sum(array_column($vendasUltimos7Dias, 'total')), 2, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-800 rounded-xl">
                    <span class="text-sm text-slate-600 dark:text-slate-300">Média diária</span>
                    <span class="font-bold text-slate-900 dark:text-white">
                        R$ {{ number_format(array_sum(array_column($vendasUltimos7Dias, 'total')) / 7, 2, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-dark-800 rounded-xl">
                    <span class="text-sm text-slate-600 dark:text-slate-300">Melhor dia</span>
                    <span class="font-bold text-emerald-500">
                        R$ {{ number_format(max(array_column($vendasUltimos7Dias, 'total')), 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>
