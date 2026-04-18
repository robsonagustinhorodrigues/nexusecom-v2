<div>
    <header class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Associação de SKUs NF-e</h2>
        <p class="text-slate-500 dark:text-slate-400">Associe os itens das notas fiscais com os produtos do sistema</p>
    </header>

    @if(session()->has('message'))
        <div class="mb-4 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
            <p class="text-emerald-400 font-medium">{{ session('message') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm mb-6">
        <div class="flex flex-col md:flex-row gap-4 mb-4">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Empresa</label>
                <select wire:model="empresaId" wire:change="changeEmpresa($event.target.value)" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium">
                    @foreach($empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Buscar</label>
                <input type="text" wire:model="search" placeholder="Buscar por SKU, GTIN ou descrição..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium">
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">GTIN</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Descrição</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">NCM</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-dark-800">
                @forelse($itens as $item)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $item->codigo_produto ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $item->gtin ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-900 dark:text-white">{{ Str::limit($item->descricao, 50) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $item->ncm ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="abrirAssociar('{{ $item->codigo_produto }}')" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg">
                                <i class="fas fa-link mr-1"></i> Associar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                            <i class="fas fa-check-circle text-4xl text-emerald-500 mb-3"></i>
                            <p>Nenhum item sem associação encontrado!</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($itens->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-dark-800">
                {{ $itens->links() }}
            </div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-dark-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Associar SKU: {{ $skuSelecionado }}</h3>
                </div>
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Buscar Produto</label>
                        <input type="text" wire:model="search" placeholder="Digite o nome, SKU ou EAN do produto..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium">
                    </div>

                    <div class="space-y-2">
                        @forelse($produtos as $produto)
                            <label class="flex items-center p-4 border border-slate-200 dark:border-dark-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-dark-800/50 {{ $produtoSelecionado == $produto->id ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : '' }}">
                                <input type="radio" name="produto" value="{{ $produto->id }}" wire:model="produtoSelecionado" class="mr-4">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $produto->nome }}</p>
                                    <p class="text-xs text-slate-500">
                                        SKU: {{ $produto->ean ?? '-' }} | EAN: {{ $produto->ean ?? '-' }}
                                    </p>
                                </div>
                                @if($produto->imagem)
                                    <img src="{{ Storage::url($produto->imagem) }}" class="w-10 h-10 object-cover rounded-lg">
                                @endif
                            </label>
                        @empty
                            <p class="text-center text-slate-500 py-4">Nenhum produto encontrado</p>
                        @endforelse
                    </div>
                </div>
                <div class="p-6 border-t border-slate-200 dark:border-dark-800 flex justify-end gap-3">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-xl hover:bg-slate-200 dark:hover:bg-dark-700">
                        Cancelar
                    </button>
                    <button wire:click="associar" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl" {{ !$produtoSelecionado ? 'disabled' : '' }}>
                        <i class="fas fa-link mr-1"></i> Associar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
