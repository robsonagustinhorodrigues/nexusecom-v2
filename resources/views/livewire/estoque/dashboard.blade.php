<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 md:p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 md:w-12 h-10 md:h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 flex-shrink-0">
                    <i class="fas fa-chart-line text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Estoque</h2>
                    <p class="text-xs md:text-sm text-slate-500 hidden md:block">Dashboard e controle</p>
                </div>
            </div>
            
            <div class="flex gap-2">
                <select wire:model="empresa_selecionada" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm">
                    @foreach($empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>
                    @endforeach
                </select>
                <select wire:model="deposito_selecionado" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm">
                    <option value="">Todos depósitos</option>
                    @foreach($depositos as $deposito)
                        <option value="{{ $deposito->id }}">{{ $deposito->nome }}</option>
                    @endforeach
                </select>
                <a href="{{ route('estoque.movimentacoes') }}" class="px-3 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white text-sm font-medium">
                    <i class="fas fa-plus mr-1"></i> Movimentar
                </a>
            </div>
        </div>
    </div>

    <!-- Cards de Totais -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-boxes text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500">SKUs</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($total_skus) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <i class="fas fa-cubes text-emerald-600 dark:text-emerald-400"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Estoque</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($total_estoque) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Valor Estimado</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">R$ {{ number_format($total_valor, 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-amber-600 dark:text-amber-400"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Estoque Baixo</p>
                    <p class="text-xl font-bold text-amber-600">{{ number_format($estoque_minimo_itens) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Lista -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Movimentações Recentes -->
        <div class="lg:col-span-2 bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
            <h3 class="font-bold text-slate-900 dark:text-white mb-4">Saldos por SKU</h3>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-dark-800 border-b border-slate-200 dark:border-dark-700">
                        <tr>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">SKU</th>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Produto</th>
                            <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500">Depósito</th>
                            <th class="text-right px-3 py-2 text-xs font-semibold text-slate-500">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                        @forelse($saldos as $saldo)
                            <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50">
                                <td class="px-3 py-2 text-sm font-medium text-slate-900 dark:text-white">
                                    {{ $saldo->sku?->sku ?? '-' }}
                                </td>
                                <td class="px-3 py-2 text-sm text-slate-600 dark:text-slate-400 max-w-xs truncate">
                                    {{ $saldo->sku?->product?->nome ?? '-' }}
                                </td>
                                <td class="px-3 py-2 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $saldo->deposito?->nome ?? '-' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span class="text-sm font-bold {{ $saldo->saldo < 5 ? 'text-amber-600' : 'text-emerald-600' }}">
                                        {{ $saldo->saldo }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-slate-500">
                                    Nenhum saldo encontrado
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($saldos->hasPages())
                <div class="mt-4">
                    {{ $saldos->links() }}
                </div>
            @endif
        </div>

        <!-- Lado Direito -->
        <div class="space-y-4">
            <!-- Top Saídas -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
                <h3 class="font-bold text-slate-900 dark:text-white mb-3">Top Saídas (30 dias)</h3>
                @forelse($top_sku as $item)
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-dark-800 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                {{ $item->sku?->sku ?? '-' }}
                            </p>
                            <p class="text-xs text-slate-500 truncate">
                                {{ $item->sku?->product?->nome ?? '' }}
                            </p>
                        </div>
                        <span class="ml-2 text-sm font-bold text-rose-600">
                            -{{ $item->total_saida }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Nenhuma saída</p>
                @endforelse
            </div>

            <!-- Entradas Recentes -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
                <h3 class="font-bold text-slate-900 dark:text-white mb-3">Entradas Recentes</h3>
                @forelse($entradas_recentes as $entrada)
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-dark-800 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                {{ $entrada->sku?->sku ?? '-' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $entrada->created_at->format('d/m H:i') }}
                            </p>
                        </div>
                        <span class="ml-2 text-sm font-bold text-emerald-600">
                            +{{ $entrada->quantidade }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Nenhuma entrada</p>
                @endforelse
            </div>

            <!-- Saídas Recentes -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
                <h3 class="font-bold text-slate-900 dark:text-white mb-3">Saídas Recentes</h3>
                @forelse($saidas_recentes as $saida)
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-dark-800 last:border-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                {{ $saida->sku?->sku ?? '-' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $saida->created_at->format('d/m H:i') }}
                            </p>
                        </div>
                        <span class="ml-2 text-sm font-bold text-rose-600">
                            -{{ $saida->quantidade }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Nenhuma saída</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
