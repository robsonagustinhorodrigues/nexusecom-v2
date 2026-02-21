<div class="space-y-6">
    <!-- Loading Indicator -->
    <div wire:loading class="bg-blue-500/10 border border-blue-500/20 text-blue-400 px-4 py-3 rounded-xl flex items-center gap-2">
        <i class="fas fa-circle-notch fa-spin"></i>
        <span>Processando...</span>
    </div>

    <!-- Header -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center shadow-lg shadow-yellow-500/20">
                    <i class="fas fa-shopping-cart text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Pedidos</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Gestão de pedidos</p>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button wire:click="toggleView('cards')" class="px-4 py-2 rounded-xl {{ $viewMode === 'cards' ? 'bg-yellow-500 text-white' : 'bg-slate-100 dark:bg-dark-800 text-slate-600 dark:text-slate-300' }}">
                    <i class="fas fa-th-large"></i>
                </button>
                <button wire:click="toggleView('table')" class="px-4 py-2 rounded-xl {{ $viewMode === 'table' ? 'bg-yellow-500 text-white' : 'bg-slate-100 dark:bg-dark-800 text-slate-600 dark:text-slate-300' }}">
                    <i class="fas fa-list"></i>
                </button>
                <button wire:click="syncMeli" wire:loading.attr="disabled" class="px-4 py-2 rounded-xl bg-yellow-500 hover:bg-yellow-400 text-white font-semibold">
                    <i wire:loading class="fas fa-spinner animate-spin"></i>
                    <i wire:loading.remove class="fas fa-sync"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Totais -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 dark:text-slate-400">Total</div>
            <div class="text-xl font-bold text-slate-900 dark:text-white">{{ $this->totais['qtd'] }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 dark:text-slate-400">Bruto</div>
            <div class="text-xl font-bold text-emerald-500">R$ {{ number_format($this->totais['valor'], 2, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 dark:text-slate-400">Frete</div>
            <div class="text-xl font-bold text-blue-500">R$ {{ number_format($this->totais['frete'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 dark:text-slate-400">Taxas</div>
            <div class="text-xl font-bold text-rose-500">R$ {{ number_format($this->totais['taxas'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
            <div class="text-xs text-slate-500 dark:text-slate-400">Líquido</div>
            <div class="text-xl font-bold text-indigo-500">R$ {{ number_format($this->totais['liquido'] ?? 0, 2, ',', '.') }}</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input wire:model.live="search" type="text" placeholder="Buscar pedido..." class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white">
            <select wire:model.live="status" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white">
                <option value="">Todos Status</option>
                <option value="em_aberto">Aberto</option>
                <option value="enviado">Enviado</option>
                <option value="entregue">Entregue</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <select wire:model.live="marketplace" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white">
                <option value="">Todos Marketplaces</option>
                <option value="mercadolivre">Mercado Livre</option>
                <option value="amazon">Amazon</option>
                <option value="bling">Bling</option>
            </select>
            <input wire:model.live="dataDe" type="date" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white">
            <input wire:model.live="dataAte" type="date" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white">
        </div>
    </div>

    <!-- Mensagem Sync -->
    @if($syncMessage)
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-xl">
        {{ $syncMessage }}
    </div>
    @endif

    <!-- LISTA DE PEDIDOS -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl overflow-hidden">
        @if($viewMode === 'cards')
            <!-- MODO CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                @forelse($this->pedidos as $pedido)
                <div class="border border-slate-200 dark:border-dark-700 rounded-xl overflow-hidden hover:shadow-lg transition bg-slate-50 dark:bg-dark-950">
                    <!-- Header -->
                    <div class="p-3 {{ $pedido->marketplace === 'mercadolivre' ? 'bg-yellow-50 dark:bg-yellow-900/20' : ($pedido->marketplace === 'amazon' ? 'bg-orange-50 dark:bg-orange-900/20' : 'bg-blue-50 dark:bg-blue-900/20') }}">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-slate-900 dark:text-white">#{{ $pedido->pedido_id }}</span>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                {{ $pedido->status === 'em_aberto' ? 'bg-emerald-500 text-white' : '' }}
                                {{ $pedido->status === 'enviado' ? 'bg-blue-500 text-white' : '' }}
                                {{ $pedido->status === 'entregue' ? 'bg-purple-500 text-white' : '' }}
                                {{ $pedido->status === 'cancelado' ? 'bg-rose-500 text-white' : '' }}
                            ">
                                {{ $pedido->status }}
                            </span>
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-300 mt-1">{{ ucfirst($pedido->marketplace) }}</div>
                    </div>
                    <!-- Body -->
                    <div class="p-3">
                        <div class="text-sm mb-2">
                            <span class="text-slate-500 dark:text-slate-400">Comprador:</span><br>
                            <span class="font-medium text-slate-900 dark:text-white">
                                {{ $pedido->comprador_nome ?: ($pedido->comprador_email ?: $pedido->comprador_cpf ?: 'Não identificado') }}
                            </span>
                        </div>
                        <!-- Lucratividade -->
                        @php
                            $frete = $pedido->valor_frete ?? 0;
                            $taxaPlatform = $pedido->valor_taxa_platform ?? 0;
                            $taxaPagamento = $pedido->valor_taxa_pagamento ?? 0;
                            $taxaFixa = $pedido->valor_taxa_fixa ?? 0;
                            $outros = $pedido->valor_outros ?? 0;
                            
                            // Calcular imposto (10% do valor dos produtos se não houver)
                            $imposto = $pedido->valor_imposto ?? 0;
                            if ($imposto == 0) {
                                $valorProdutosTemp = $pedido->valor_produtos ?? ($pedido->valor_total - $frete);
                                $imposto = $valorProdutosTemp * 0.10; // 10% imposto
                            }
                            
                            $valorProdutos = $pedido->valor_produtos ?? ($pedido->valor_total - $frete);
                            $valorLiquido = $pedido->valor_liquido ?? $pedido->valor_total;
                            
                            $totalTaxas = $taxaPlatform + $taxaPagamento + $taxaFixa + $imposto + $outros;
                            $lucro = $valorLiquido - $valorProdutos - $frete;
                            $margem = $valorProdutos > 0 ? round(($lucro / $valorProdutos) * 100, 1) : 0;
                        @endphp
                        <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-dark-700">
                            <div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Produtos</div>
                                <div class="text-sm font-semibold text-slate-900 dark:text-white">R$ {{ number_format($valorProdutos, 2, ',', '.') }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Frete</div>
                                <div class="text-sm font-semibold text-blue-600">R$ {{ number_format($frete, 2, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Taxas</div>
                                <div class="text-sm font-semibold text-rose-600">
                                    -R$ {{ number_format($totalTaxas, 2, ',', '.') }}
                                    @if($imposto > 0)<span class="text-xs">(Imposto: R$ {{ number_format($imposto, 2, ',', '.') }})</span>@endif
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Margem</div>
                                <div class="text-sm font-semibold {{ $margem >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $margem }}%
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Líquido</div>
                                <div class="text-sm font-semibold text-emerald-600">R$ {{ number_format($valorLiquido, 2, ',', '.') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-slate-500 dark:text-slate-400">Total</div>
                                <div class="text-lg font-bold text-slate-900 dark:text-white">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12 text-slate-500 dark:text-slate-400">
                    <i class="fas fa-inbox text-4xl mb-3"></i>
                    <p>Nenhum pedido encontrado</p>
                </div>
                @endforelse
            </div>
        @else
            <!-- MODO TABELA -->
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-dark-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">Pedido</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">Marketplace</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">Comprador</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Produtos</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Frete</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Imposto</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Taxas</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Líquido</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Margem</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-dark-700">
                    @forelse($this->pedidos as $pedido)
                    @php
                        $frete = $pedido->valor_frete ?? 0;
                        $taxaPlatform = $pedido->valor_taxa_platform ?? 0;
                        $taxaPagamento = $pedido->valor_taxa_pagamento ?? 0;
                        $taxaFixa = $pedido->valor_taxa_fixa ?? 0;
                        $outros = $pedido->valor_outros ?? 0;
                        
                        // Calcular imposto (10% do valor dos produtos se não houver)
                        $imposto = $pedido->valor_imposto ?? 0;
                        if ($imposto == 0) {
                            $valorProdutosTemp = $pedido->valor_produtos ?? ($pedido->valor_total - $frete);
                            $imposto = $valorProdutosTemp * 0.10;
                        }
                        
                        $valorProdutos = $pedido->valor_produtos ?? ($pedido->valor_total - $frete);
                        $valorLiquido = $pedido->valor_liquido ?? $pedido->valor_total;
                        
                        $totalTaxas = $taxaPlatform + $taxaPagamento + $taxaFixa + $imposto + $outros;
                        $lucro = $valorLiquido - $valorProdutos - $frete;
                        $margem = $valorProdutos > 0 ? round(($lucro / $valorProdutos) * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800">
                        <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">#{{ $pedido->pedido_id }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs font-semibold
                                {{ $pedido->marketplace === 'mercadolivre' ? 'bg-yellow-500 text-white' : '' }}
                                {{ $pedido->marketplace === 'amazon' ? 'bg-orange-500 text-white' : '' }}
                                {{ $pedido->marketplace === 'bling' ? 'bg-blue-500 text-white' : '' }}
                            ">{{ ucfirst($pedido->marketplace) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-900 dark:text-white">
                            {{ $pedido->comprador_nome ?: ($pedido->comprador_email ?: $pedido->comprador_cpf ?: 'Não identificado') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                {{ $pedido->status === 'em_aberto' ? 'bg-emerald-500 text-white' : '' }}
                                {{ $pedido->status === 'enviado' ? 'bg-blue-500 text-white' : '' }}
                                {{ $pedido->status === 'entregue' ? 'bg-purple-500 text-white' : '' }}
                                {{ $pedido->status === 'cancelado' ? 'bg-rose-500 text-white' : '' }}
                            ">{{ $pedido->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-900 dark:text-white">R$ {{ number_format($valorProdutos, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-blue-600">R$ {{ number_format($frete, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-amber-600">R$ {{ number_format($imposto, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-rose-600">-R$ {{ number_format($totalTaxas, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-emerald-600">R$ {{ number_format($valorLiquido, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $margem >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $margem }}%</td>
                        <td class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-slate-500 dark:text-slate-400">{{ $pedido->data_compra?->format('d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p>Nenhum pedido encontrado</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        <!-- Paginação -->
        @if($this->pedidos->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-dark-800">
            {{ $this->pedidos->links() }}
        </div>
        @endif
    </div>
</div>
