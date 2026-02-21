<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 md:p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 md:w-12 h-10 md:h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 flex-shrink-0">
                    <i class="fas fa-exchange-alt text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Movimentações</h2>
                    <p class="text-xs md:text-sm text-slate-500 hidden md:block">Controle de estoque</p>
                </div>
            </div>
            
            <button wire:click="novaMovimentacao" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white font-semibold text-sm transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Nova Movimentação</span>
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Empresa</label>
                <select wire:model.live="filtroEmpresa" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm">
                    <option value="">Todas</option>
                    @foreach($empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Depósito</label>
                <select wire:model.live="filtroDeposito" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach($depositos as $deposito)
                        <option value="{{ $deposito->id }}">{{ $deposito->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
                <select wire:model.live="filtroTipo" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                    <option value="perda">Perda</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">De</label>
                <input wire:model.live="filtroDataDe" type="date" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Até</label>
                <input wire:model.live="filtroDataAte" type="date" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">SKU</label>
                <input wire:model.live="filtroSKU" type="text" placeholder="Buscar SKU..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <!-- Lista -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-dark-800 border-b border-slate-200 dark:border-dark-700">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Data</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">SKU</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Depósito</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Qtd</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Documento</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Obs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                    @forelse($this->movimentacoes as $mov)
                        <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50">
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                {{ $mov->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $mov->sku?->sku ?? '-' }}</span>
                                @if($mov->sku?->product)
                                    <p class="text-xs text-slate-500">{{ $mov->sku->product->nome }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                {{ $mov->deposito?->nome ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @php $tipoLabel = match($mov->tipo) {
                                    'entrada' => ['label' => 'Entrada', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'],
                                    'saida' => ['label' => 'Saída', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'],
                                    'perda' => ['label' => 'Perda', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                                    default => ['label' => $mov->tipo, 'class' => 'bg-slate-100 text-slate-700']
                                }; @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tipoLabel['class'] }}">
                                    {{ $tipoLabel['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium {{ $mov->tipo === 'entrada' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $mov->tipo === 'entrada' ? '+' : '-' }}{{ $mov->quantidade }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                @if($mov->documento_tipo)
                                    <span class="text-xs">{{ getDocTipoLabel($mov->documento_tipo) }}</span>
                                @endif
                                @if($mov->documento)
                                    <p class="text-xs text-slate-500">{{ $mov->documento }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-500 max-w-xs truncate">
                                {{ $mov->observacao ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @if(!$mov->movimentacao_estornada_id && $mov->tipo !== 'perda')
                                    <button 
                                        wire:click="estornar({{ $mov->id }})" 
                                        wire:confirm="Estornar esta movimentação? Isso criará uma movimentação oposta."
                                        class="text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                        title="Estornar"
                                    >
                                        <i class="fas fa-undo"></i>
                                    </button>
                                @elseif($mov->movimentacao_estornada_id)
                                    <span class="text-xs text-slate-400" title="Estornada">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                <i class="fas fa-box text-4xl text-slate-300 mb-4 block"></i>
                                Nenhuma movimentação encontrada
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($this->movimentacoes->hasPages())
            <div class="px-4 py-3 border-t border-slate-200 dark:border-dark-700">
                {{ $this->movimentacoes->links() }}
            </div>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" wire:click.self="showModal = false">
            <div class="bg-white dark:bg-dark-900 rounded-2xl p-6 w-full max-w-lg shadow-xl">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">
                    Nova Movimentação
                </h3>
                
                <div class="space-y-4">
                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo</label>
                        <div class="flex gap-2">
                            <button wire:click="$set('tipo', 'entrada')" class="flex-1 py-2 rounded-xl text-sm font-medium transition-all {{ $tipo === 'entrada' ? 'bg-emerald-500 text-white' : 'bg-slate-100 dark:bg-dark-800 text-slate-600' }}">
                                <i class="fas fa-arrow-down mr-1"></i> Entrada
                            </button>
                            <button wire:click="$set('tipo', 'saida')" class="flex-1 py-2 rounded-xl text-sm font-medium transition-all {{ $tipo === 'saida' ? 'bg-rose-500 text-white' : 'bg-slate-100 dark:bg-dark-800 text-slate-600' }}">
                                <i class="fas fa-arrow-up mr-1"></i> Saída
                            </button>
                        </div>
                    </div>
                    
                    <!-- SKU -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">SKU / Produto</label>
                        <select wire:model="product_sku_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach(\App\Models\ProductSku::with('product')->limit(50)->get() as $sku)
                                <option value="{{ $sku->id }}">{{ $sku->sku }} - {{ $sku->product?->nome ?? 'Sem produto' }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Depósito -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Depósito</label>
                        <select wire:model="deposito_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach($depositos as $deposito)
                                <option value="{{ $deposito->id }}">{{ $deposito->nome }} ({{ $deposito->tipo }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Quantidade -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Quantidade</label>
                        <input wire:model="quantidade" type="number" min="1" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                    </div>
                    
                    <!-- Documento -->
                    @if($tipo === 'entrada')
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo Documento</label>
                        <select wire:model="documento_tipo" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                            <option value="">Selecione...</option>
                            <option value="nfe_compra">NF-e Compra</option>
                            <option value="nfe_devolucao">NF-e Devolução</option>
                            <option value="ajuste">Ajuste</option>
                        </select>
                    </div>
                    
                    @if($documento_tipo === 'nfe_devolucao')
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Produto em boas condições?</label>
                        <div class="flex items-center gap-3">
                            <input wire:model="produto_bom" type="checkbox" id="produto_bom" class="w-4 h-4 rounded border-slate-300 text-emerald-600">
                            <label for="produto_bom" class="text-sm text-slate-600">Sim, volta ao estoque</label>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Se não estiver bom, será registrado como perda</p>
                    </div>
                    @endif
                    @endif
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Número Documento</label>
                        <input wire:model="documento" type="text" placeholder="NF-e, pedido, etc" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                    </div>
                    
                    <!-- Valor -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Valor Unitário (R$)</label>
                        <input wire:model="valor_unitario" type="number" step="0.01" placeholder="0,00" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                    </div>
                    
                    <!-- Observação -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Observação</label>
                        <textarea wire:model="observacao" rows="2" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm"></textarea>
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button wire:click="showModal = false" class="flex-1 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-dark-800 dark:hover:bg-dark-700 text-slate-700 dark:text-slate-300 font-medium transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="salvar" class="flex-1 px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-white font-medium transition-colors">
                        Salvar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Message -->
    @if(session('success'))
        <div class="fixed bottom-4 right-4 bg-emerald-500 text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif
</div>
