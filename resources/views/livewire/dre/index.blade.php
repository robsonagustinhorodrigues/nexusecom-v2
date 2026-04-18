<div>
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        DRE - Demonstração do Resultado do Exercício
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Demonstração de Resultsdo do Período
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Ano</label>
                        <select wire:model="ano" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($anos as $ano)
                                <option value="{{ $ano }}">{{ $ano }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Mês</label>
                        <select wire:model="mes" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @foreach($meses as $num => $nome)
                                <option value="{{ $num }}">{{ $nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">
            @php($dre = $this->dreData)
            @php($despesas = $this->despesasDetalhadas)
            
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Receita Bruta de Vendas</span>
                    <span class="text-lg font-semibold text-green-600">R$ {{ number_format($dre['receita_bruta'], 2, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b text-red-600">
                    <span>(-) Deduções</span>
                    <span>(R$ {{ number_format($dre['deducoes'], 2, ',', '.') }})</span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b bg-gray-50 dark:bg-gray-700/50">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Receita Líquida</span>
                    <span class="text-xl font-bold text-green-600">R$ {{ number_format($dre['receita_liquida'], 2, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b text-red-600">
                    <span>(-) Custo das Mercadorias Vendidas</span>
                    <span>(R$ {{ number_format($dre['cmv'], 2, ',', '.') }})</span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b bg-gray-50 dark:bg-gray-700/50">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Lucro Bruto</span>
                    <span class="text-xl font-bold {{ $dre['lucro_bruto'] >= 0 ? 'text-green-600' : 'text-red-600' }}">R$ {{ number_format($dre['lucro_bruto'], 2, ',', '.') }}</span>
                </div>
                
                <div class="mt-4">
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Despesas Operacionais</h3>
                    
                    @if(count($despesas) > 0)
                        @foreach($despesas as $categoria => $valor)
                            <div class="flex justify-between items-center py-2 border-b border-dashed">
                                <span class="text-gray-600 dark:text-gray-400">{{ \App\Models\Despesa::getCategorias()[$categoria] ?? $categoria }}</span>
                                <span class="text-red-600">R$ {{ number_format($valor, 2, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-500 py-2">Nenhuma despesa registrada no período</p>
                    @endif
                </div>
                
                <div class="flex justify-between items-center py-2 border-b text-red-600">
                    <span>(-) Total Despesas</span>
                    <span>(R$ {{ number_format($dre['despesas'], 2, ',', '.') }})</span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b bg-gray-50 dark:bg-gray-700/50">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Resultado Operacional</span>
                    <span class="text-xl font-bold {{ $dre['resultado_operacional'] >= 0 ? 'text-green-600' : 'text-red-600' }}">R$ {{ number_format($dre['resultado_operacional'], 2, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b text-red-600">
                    <span>(-) Impostos ({{ ucfirst($regimeTributario) }})</span>
                    <span>(R$ {{ number_format($dre['impostos'], 2, ',', '.') }})</span>
                </div>
                
                <div class="flex justify-between items-center py-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg px-4">
                    <span class="text-lg font-bold text-gray-700 dark:text-gray-300">Lucro Líquido</span>
                    <span class="text-2xl font-bold {{ $dre['lucro_liquido'] >= 0 ? 'text-green-600' : 'text-red-600' }}">R$ {{ number_format($dre['lucro_liquido'], 2, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between items-center text-sm text-gray-500">
                    <span>Margem de Lucro</span>
                    <span class="{{ $dre['margem_lucro'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($dre['margem_lucro'], 1, ',', '.') }}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Resumo por Categoria
                </h3>
                <a href="{{ route('finances.despesas') }}" class="text-sm text-blue-600 hover:text-blue-800">
                    Gerenciar Despesas →
                </a>
            </div>
            
            @if(count($despesas) > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach($despesas as $categoria => $valor)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ \App\Models\Despesa::getCategorias()[$categoria] ?? $categoria }}
                            </p>
                            <p class="text-lg font-semibold text-red-600 mt-1">
                                R$ {{ number_format($valor, 2, ',', '.') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-8">
                    Nenhuma despesa registrada no período.
                </p>
            @endif
        </div>
    </div>

    <div class="mt-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Informações do Período
            </h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Mês/Ano</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $meses[$mes] }}/{{ $ano }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Regime Tributário</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $regimeTributario)) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Qtd. Pedidos</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        @php($pedidos = \App\Models\MarketplacePedido::where('empresa_id', auth()->user()->current_empresa_id)
                            ->whereIn('status_pagamento', ['paid', 'approved', 'pago'])
                            ->whereYear('data_pagamento', $ano)
                            ->whereMonth('data_pagamento', $mes)
                            ->count())
                        {{ $pedidos }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ticket Médio</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        R$ {{ $pedidos > 0 ? number_format($dre['receita_bruta'] / $pedidos, 2, ',', '.') : '0,00' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
