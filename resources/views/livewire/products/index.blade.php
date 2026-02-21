<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fas fa-box text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Catálogo de Produtos</h2>
                <p class="text-sm text-slate-500">Gerencie seu catálogo de produtos</p>
            </div>
        </div>
        <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all">
            <i class="fas fa-plus"></i>
            Novo Produto
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                    <i class="fas fa-box text-indigo-500"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
                    <p class="text-xs text-slate-500">Total de Produtos</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <i class="fas fa-box text-blue-500"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['simples'] }}</p>
                    <p class="text-xs text-slate-500">Produtos Simples</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                    <i class="fas fa-layer-group text-purple-500"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['variacao'] }}</p>
                    <p class="text-xs text-slate-500">Variações</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <input 
                    wire:model.live="search" 
                    type="text" 
                    placeholder="Buscar produtos..."
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm"
                >
            </div>
            <div>
                <select wire:model.live="categoria_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                    <option value="">Todas Categorias</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="tipo" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2 text-sm">
                    <option value="">Todos Tipos</option>
                    <option value="simples">Simples</option>
                    <option value="variacao">Variação</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($products as $product)
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                <div class="aspect-square bg-slate-100 dark:bg-dark-800 flex items-center justify-center relative">
                    @if($product->imagem)
                        <img src="{{ $product->imagem }}" alt="{{ $product->nome }}" class="w-full h-full object-cover">
                    @else
                        <i class="fas fa-box text-4xl text-slate-300"></i>
                    @endif
                    @if(!$product->ativo)
                        <div class="absolute top-2 right-2">
                            <span class="px-2 py-1 bg-slate-500 text-white text-xs font-medium rounded-full">Inativo</span>
                        </div>
                    @endif
                </div>
                <div class="p-4">
                    <p class="text-xs text-slate-500 mb-1">{{ $product->skus->first()?->sku ?? 'Sem SKU' }}</p>
                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $product->nome }}</h3>
                    @if($product->tags && count($product->tags) > 0)
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach($product->tags as $tag)
                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-dark-700 text-slate-600 dark:text-slate-300 text-xs rounded-full">{{ $tag }}</span>
                        @endforeach
                    </div>
                    @endif
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs px-2 py-1 rounded-full {{ $product->tipo === 'simples' ? 'bg-blue-500/10 text-blue-500' : 'bg-purple-500/10 text-purple-500' }}">
                            {{ $product->tipo === 'simples' ? 'Simples' : 'Variação' }}
                        </span>
                    </div>
                    @if($product->usar_virtual)
                    <div class="flex items-center gap-2 mt-2 text-xs">
                        <span class="px-2 py-1 rounded-full bg-purple-500/10 text-purple-500">
                            <i class="fas fa-box mr-1"></i>Virtual: {{ $product->quantidade_virtual }}
                        </span>
                    </div>
                    @endif
                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100 dark:border-dark-800">
                        <a href="{{ route('products.edit', ['id' => $product->id]) }}" class="flex-1 flex items-center justify-center gap-1 px-3 py-2 bg-indigo-500/10 text-indigo-500 hover:bg-indigo-500/20 rounded-lg text-sm font-medium transition-all">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <button 
                            wire:click="toggleStatus({{ $product->id }})" 
                            class="p-2 rounded-lg {{ $product->ativo ? 'text-amber-500 hover:bg-amber-500/10' : 'text-emerald-500 hover:bg-emerald-500/10' }} transition-all"
                            title="{{ $product->ativo ? 'Inativar' : 'Ativar' }}">
                            <i class="fas fa-{{ $product->ativo ? 'ban' : 'check' }}"></i>
                        </button>
                        <button 
                            wire:click="deleteProduct({{ $product->id }})" 
                            wire:confirm="Tem certeza que deseja excluir este produto?"
                            class="p-2 rounded-lg text-rose-500 hover:bg-rose-500/10 transition-all"
                            title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-box text-4xl text-slate-300 mb-4"></i>
                <p class="text-slate-500">Nenhum produto encontrado</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $products->links() }}
    </div>
</div>
