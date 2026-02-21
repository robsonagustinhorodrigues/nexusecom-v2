<div class="space-y-6">
    <!-- Header com Informações Principais -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fas fa-box text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Cadastro de Produtos</h2>
                <p class="text-sm text-slate-500">Gerencie seu catálogo de produtos</p>
            </div>
        </div>

        <div class="flex gap-3 items-center">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model="ativo" class="rounded bg-dark-800 border-dark-700 text-indigo-600" checked>
                <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">Ativo</span>
            </label>
            <a href="{{ route('products.index') }}" class="px-4 py-2.5 rounded-xl bg-white dark:bg-dark-800 border border-slate-200 dark:border-dark-700 text-slate-600 dark:text-slate-400 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-dark-700 transition-all flex items-center gap-2">
                <i class="fas fa-list text-xs"></i>
                Ver Todos
            </a>
            <button wire:click="save" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
                <i class="fas fa-save text-xs"></i>
                {{ $productId ? 'Atualizar Produto' : 'Salvar Produto' }}
            </button>
        </div>
    </div>

    <!-- Alertas -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('message') }}
        </div>
    @endif

    <!-- Barra de Ações Rápidas -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-4 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center gap-4">
            <!-- Tipo de Produto -->
            <div class="flex bg-slate-100 dark:bg-dark-800 rounded-xl p-1">
                <button 
                    wire:click="$set('tipo', 'simples')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2 {{ $tipo === 'simples' ? 'bg-white dark:bg-dark-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-white' }}"
                >
                    <i class="fas fa-box text-xs"></i>
                    Produto Simples
                </button>
                <button 
                    wire:click="$set('tipo', 'variacao')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2 {{ $tipo === 'variacao' ? 'bg-white dark:bg-dark-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-white' }}"
                >
                    <i class="fas fa-layer-group text-xs"></i>
                    Com Variações
                </button>
                <button 
                    wire:click="$set('tipo', 'composto')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2 {{ $tipo === 'composto' ? 'bg-white dark:bg-dark-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-white' }}"
                >
                    <i class="fas fa-boxes text-xs"></i>
                    Kit / Composto
                </button>
            </div>
        </div>
    </div>

    <!-- Formulário em Grid -->
    <div class="grid grid-cols-1 gap-6">
        <!-- Variações (se aplicável) - LARGURA TOTAL -->
        @if($tipo === 'variacao')
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm w-full">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-layer-group text-amber-500"></i>
                    Variações do Produto
                </h3>
                <button wire:click="addGroup" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 flex items-center gap-1">
                    <i class="fas fa-plus"></i>
                    Adicionar Atributo
                </button>
            </div>
                
            @if(count($variantGroups) > 0)
            <div class="space-y-4">
                @foreach($variantGroups as $gIndex => $group)
                <div class="p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Atributo</label>
                            <input wire:model.blur="variantGroups.{{ $gIndex }}.name" type="text" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-2 text-sm font-medium uppercase" placeholder="Ex: COR, TAMANHO">
                        </div>
                        <div class="flex-[2]">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Valores</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($group['values'] as $vIndex => $val)
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold">
                                    {{ $val }}
                                    <button wire:click="removeVariantValue({{ $gIndex }}, {{ $vIndex }})" class="hover:text-indigo-900 dark:hover:text-indigo-100">
                                        <i class="fas fa-times text-[10px]"></i>
                                    </button>
                                </span>
                                @endforeach
                                <input 
                                    type="text" 
                                    x-on:keydown.enter.prevent="$wire.addVariantValue({{ $gIndex }}, $event.target.value); $event.target.value = ''" 
                                    class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-1 text-xs font-medium w-32 placeholder:text-slate-400"
                                    placeholder="Adicionar..."
                                >
                            </div>
                        </div>
                        @if($gIndex > 0)
                        <button wire:click="removeGroup({{ $gIndex }})" class="text-rose-500 hover:text-rose-400 p-2">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-8 text-center border-2 border-dashed border-slate-200 dark:border-dark-700 rounded-xl">
                <i class="fas fa-layer-group text-3xl text-slate-300 dark:text-slate-600 mb-3"></i>
                <p class="text-sm text-slate-500">Clique em "Adicionar Atributo" para criar variações</p>
            </div>
            @endif

            <!-- Tabela de Variações -->
            @if(count($variations) > 0)
            <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 dark:border-dark-700">
                <table class="w-full text-left text-sm min-w-[800px]">
                    <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-700">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs">Variação</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-40">SKU</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-32">Venda</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-32">Custo</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-32">Estoque</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-40">Fornecedor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                        @foreach($variations as $vIdx => $v)
                        <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $v['label'] }}</td>
                            <td class="px-4 py-3">
                                <input wire:model="variations.{{ $vIdx }}.sku" type="text" class="w-full bg-transparent border border-slate-200 dark:border-dark-700 rounded-lg px-2 py-1 text-xs font-mono uppercase">
                            </td>
                            <td class="px-4 py-3">
                                <input wire:model="variations.{{ $vIdx }}.preco_venda" type="number" step="0.01" class="w-full bg-transparent border border-slate-200 dark:border-dark-700 rounded-lg px-2 py-1 text-xs font-mono text-emerald-600">
                            </td>
                            <td class="px-4 py-3">
                                <input wire:model="variations.{{ $vIdx }}.preco_custo" type="number" step="0.01" class="w-full bg-transparent border border-slate-200 dark:border-dark-700 rounded-lg px-2 py-1 text-xs font-mono">
                            </td>
                            <td class="px-4 py-3">
                                <input wire:model="variations.{{ $vIdx }}.estoque" type="number" class="w-full bg-transparent border border-slate-200 dark:border-dark-700 rounded-lg px-2 py-1 text-xs font-mono text-indigo-600">
                            </td>
                            <td class="px-4 py-3">
                                <select wire:model="variations.{{ $vIdx }}.fornecedor_id" class="w-full bg-transparent border border-slate-200 dark:border-dark-700 rounded-lg px-2 py-1 text-xs">
                                    <option value="">Selecione...</option>
                                    @foreach($this->fornecedores as $f)
                                        <option value="{{ $f->id }}">{{ $f->nome }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        <!-- Produto Composto / Kit -->
        @if($tipo === 'composto')
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-boxes text-emerald-500"></i>
                    Componentes do Kit
                </h3>
                <span class="text-sm text-slate-500">
                    Estoque máximo possível: <strong class="text-emerald-600">{{ $this->getMaxCompoundStock() }}</strong> kits
                </span>
            </div>

            <!-- Search Component -->
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Buscar Produto</label>
                <div class="relative">
                    <input 
                        wire:model="searchComponent"
                        wire:keyup.debounce.300ms="searchComponent"
                        type="text" 
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm"
                        placeholder="Digite o nome ou SKU do produto..."
                    >
                    @if(count($componentSearchResults) > 0)
                    <div class="absolute z-10 w-full mt-1 bg-white dark:bg-dark-800 border border-slate-200 dark:border-dark-700 rounded-xl shadow-lg max-h-60 overflow-auto">
                        @foreach($componentSearchResults as $result)
                        <button 
                            wire:click="addComponent({{ $result->id }})"
                            class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-dark-700 border-b border-slate-100 dark:border-dark-700 last:border-0"
                        >
                            <div class="font-medium text-slate-900 dark:text-white">{{ $result->nome }}</div>
                            <div class="text-xs text-slate-500">
                                SKU: {{ $result->sku }} | 
                                R$ {{ number_format($result->preco_venda, 2, ',', '.') }} | 
                                Estoque: {{ $result->estoque }}
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- Components List -->
            @if(count($components) > 0)
            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-dark-700">
                <table class="w-full text-left text-sm min-w-[600px]">
                    <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-700">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs">Produto</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-24">Qtd</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-32">Preço Unit.</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-24">Total</th>
                            <th class="px-4 py-3 font-semibold text-slate-500 uppercase text-xs w-16"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                        @foreach($components as $index => $comp)
                        <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $comp['nome'] }}</div>
                                <div class="text-xs text-slate-500">SKU: {{ $comp['sku'] }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="number" 
                                    min="1"
                                    wire:change="updateComponentQuantity({{ $index }}, $event.target.value)"
                                    value="{{ $comp['quantity'] }}"
                                    class="w-full bg-transparent border border-slate-200 dark:border-dark-700 rounded-lg px-2 py-1 text-xs"
                                >
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="number" 
                                    step="0.01"
                                    wire:change="updateComponentPrice({{ $index }}, $event.target.value)"
                                    value="{{ $comp['unit_price'] ?? $comp['preco_venda'] }}"
                                    class="w-full bg-transparent border border-slate-200 dark:border-dark-700 rounded-lg px-2 py-1 text-xs"
                                >
                            </td>
                            <td class="px-4 py-3 text-emerald-600 font-medium">
                                R$ {{ number_format(($comp['unit_price'] ?? $comp['preco_venda']) * $comp['quantity'], 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button 
                                    wire:click="removeComponent({{ $index }})"
                                    class="text-rose-500 hover:text-rose-400"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-dark-950 border-t border-slate-200 dark:border-dark-700">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-bold text-slate-900 dark:text-white">
                                Total dos Componentes:
                            </td>
                            <td colspan="2" class="px-4 py-3 text-emerald-600 font-bold text-lg">
                                R$ {{ number_format($this->getComponentsTotalPrice(), 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="py-8 text-center border-2 border-dashed border-slate-200 dark:border-dark-700 rounded-xl">
                <i class="fas fa-boxes text-3xl text-slate-300 dark:text-slate-600 mb-3"></i>
                <p class="text-sm text-slate-500">Nenhum produto adicionado ao kit</p>
                <p class="text-xs text-slate-400">Busque acima e adicione os produtos que fazem parte deste kit</p>
            </div>
            @endif

            <!-- Info Box -->
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-medium">Como funciona o produto composto:</p>
                        <ul class="mt-1 text-xs list-disc list-inside">
                            <li>O preço do kit é calculado automaticamente pela soma dos componentes</li>
                            <li>O estoque do kit é limitado pelo produto com menor disponibilidade</li>
                            <li>Ao vender um kit, o estoque dos componentes é reduzido automaticamente</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Anuncios Vinculados -->
        @if($productId && count($this->linkedAnuncios) > 0)
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                <i class="fas fa-link text-indigo-500"></i>
                Anuncios Vinculados ({{ count($this->linkedAnuncios) }})
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->linkedAnuncios as $anuncio)
                <div class="p-4 bg-slate-50 dark:bg-dark-950 rounded-xl border border-slate-200 dark:border-dark-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold px-2 py-1 rounded-full {{ $anuncio['marketplace'] === 'mercadolivre' ? 'bg-yellow-500/10 text-yellow-600' : ($anuncio['marketplace'] === 'amazon' ? 'bg-orange-500/10 text-orange-600' : 'bg-blue-500/10 text-blue-600') }}">
                            {{ ucfirst($anuncio['marketplace']) }}
                        </span>
                        <span class="text-xs px-2 py-1 rounded-full {{ $anuncio['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-slate-500/10 text-slate-500' }}">
                            {{ $anuncio['status'] === 'active' ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                    <p class="font-medium text-slate-900 dark:text-white text-sm line-clamp-2 mb-2">{{ $anuncio['titulo'] }}</p>
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>SKU: {{ $anuncio['sku'] }}</span>
                        <span class="font-bold text-indigo-600">R$ {{ number_format($anuncio['preco'], 2, ',', '.') }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Grid com colunas - cada seção em largura total -->
        <div class="space-y-6">
            <!-- Informações Básicas - LARGURA TOTAL -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                    <i class="fas fa-info-circle text-indigo-500"></i>
                    Informações Básicas
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nome do Produto</label>
                        <input wire:model="nome" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Digite o nome do produto...">
                        @error('nome') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Marca</label>
                        <input wire:model="marca" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Ex: Nike, Samsung...">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">EAN (Código de Barras)</label>
                        <input wire:model="ean" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="7891234567890" maxlength="20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Código SKU</label>
                        <input wire:model="sku" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 uppercase" placeholder="SKU-001">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Categoria</label>
                        <select wire:model="categoria_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 h-[50px]">
                            <option value="">Selecione...</option>
                            @foreach($this->categorias as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                                @foreach($categoria->filhas as $sub)
                                    <option value="{{ $sub->id }}">↳ {{ $sub->nome }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Descrição</label>
                    <textarea wire:model="descricao" rows="3" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Descrição do produto..."></textarea>
                </div>
            </div>

            <!-- Informações Fiscais - LARGURA TOTAL, ABAIXO das Básicas -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                    <i class="fas fa-file-invoice text-blue-500"></i>
                    Informações Fiscais
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">NCM</label>
                        <input wire:model="ncm" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="0000.00.00" maxlength="10">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">CEST</label>
                        <input wire:model="cest" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="00.000.00" maxlength="9">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Origem</label>
                        <select wire:model="origem" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500">
                            <option value="0">0 - Nacional</option>
                            <option value="1">1 - Estrangeira (Importação direta)</option>
                            <option value="2">2 - Estrangeira (Adquirida no mercado interno)</option>
                            <option value="3">3 - Nacional - Conteúdo de Importação > 40%</option>
                            <option value="4">4 - Nacional - Basics</option>
                            <option value="5">5 - Nacional - Conteúdo de Importação ≤ 40%</option>
                            <option value="6">6 - Estrangeira - REPOR (< 65)</option>
                            <option value="7">7 - Estrangeira - REPOR (≥ 65)</option>
                            <option value="8">8 - Nacional - Insumos ≥ 70%</option>
                            <option value="9">9 - Outras</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Unidade</label>
                        <select wire:model="unidade_medida" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500">
                            <option value="UN">UN - Unidade</option>
                            <option value="KG">KG - Quilograma</option>
                            <option value="G">G - Grama</option>
                            <option value="L">L - Litro</option>
                            <option value="ML">ML - Mililitro</option>
                            <option value="M">M - Metro</option>
                            <option value="CM">CM - Centímetro</option>
                            <option value="PC">PC - Peça</option>
                            <option value="CX">CX - Caixa</option>
                            <option value="FD">FD - Fardo</option>
                            <option value="SC">SC - Saco</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Precificação - LARGURA TOTAL -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                    <i class="fas fa-dollar-sign text-emerald-500"></i>
                    <i</i>
                    Precificação
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Preço de Custo</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">R$</span>
                            <input wire:model="preco_custo" type="number" step="0.01" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl pl-10 pr-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="0,00">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Preço de Venda</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium">R$</span>
                            <input wire:model="preco_venda" type="number" step="0.01" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl pl-10 pr-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="0,00">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Estoque inicial</label>
                        <input type="number" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Estoque Virtual</label>
                        <input wire:model="quantidade_virtual" type="number" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="0">
                    </div>
                    <div class="flex items-center gap-3">
                        <input wire:model="usar_virtual" type="checkbox" id="usar_virtual" class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="usar_virtual" class="text-sm text-slate-700 dark:text-slate-300">Usar estoque virtual</label>
                    </div>
                </div>
            </div>

            <!-- Fotos -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-5">
                    <i class="fas fa-images text-rose-500"></i>
                    Fotos do Produto
                </h3>
                
                <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <!-- Adicionar nova foto -->
                    <div class="aspect-square rounded-xl border-2 border-dashed border-slate-200 dark:border-dark-700 flex flex-col items-center justify-center text-slate-400 hover:border-indigo-500 hover:text-indigo-500 cursor-pointer transition-all">
                        <i class="fas fa-plus text-xl mb-1"></i>
                        <span class="text-xs font-medium">Adicionar</span>
                    </div>
                    
                    <!-- Fotos existentes (exemplo) -->
                    <div class="aspect-square rounded-xl bg-slate-100 dark:bg-dark-800 relative group overflow-hidden">
                        <img src="https://via.placeholder.com/200" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button class="p-2 bg-white/20 rounded-lg text-white hover:bg-white/30">
                                <i class="fas fa-eye text-xs"></i>
                            </button>
                            <button class="p-2 bg-white/20 rounded-lg text-white hover:bg-white/30">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna Lateral -->
        <div class="space-y-6">
            <!-- Status -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-toggle-on text-slate-400"></i>
                    Status
                </h3>
                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-dark-950 rounded-xl">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Produto Ativo</span>
                    <div class="w-11 h-6 bg-emerald-500 rounded-full relative cursor-pointer">
                        <div class="absolute right-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow"></div>
                    </div>
                </div>
            </div>

            <!-- Organização -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-folder text-slate-400"></i>
                    Organização
                </h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Categoria</label>
                        <select wire:model="categoria_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2.5 text-sm font-medium">
                            <option value="">Selecione...</option>
                            @foreach($this->categorias as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>
                                @foreach($categoria->filhas as $sub)
                                    <option value="{{ $sub->id }}">↳ {{ $sub->nome }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-tags text-slate-400"></i>
                    Tags
                </h3>
                <div class="space-y-2">
                    <div class="flex flex-wrap gap-1">
                        @foreach($tags as $index => $tag)
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold">
                            {{ $tag }}
                            <button wire:click="removeTag({{ $index }})" class="hover:text-indigo-900 dark:hover:text-indigo-100">
                                <i class="fas fa-times text-[10px]"></i>
                            </button>
                        </span>
                        @endforeach
                    </div>
                    <div class="flex gap-2">
                        <input 
                            wire:model="newTag" 
                            type="text" 
                            x-on:keydown.enter.prevent="$wire.addTag(); $event.target.value = ''"
                            class="flex-1 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2 text-xs font-medium placeholder:text-slate-400"
                            placeholder="Adicionar tag..."
                        >
                        <button wire:click="addTag" class="px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-500">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Fornecedor -->
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-truck text-slate-400"></i>
                    Fornecedor
                </h3>
                <div>
                    <select wire:model="fornecedor_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-2.5 text-sm font-medium">
                        <option value="">Selecione...</option>
                        @foreach($this->fornecedores as $fornecedor)
                            <option value="{{ $fornecedor->id }}">{{ $fornecedor->razao_social }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Resumo -->
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl p-5 text-white">
                <h3 class="text-sm font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie"></i>
                    Resumo
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-indigo-200">Tipo</span>
                        <span class="font-semibold">
                            @if($tipo === 'variacao') Com Variações
                            @elseif($tipo === 'composto') Kit / Composto
                            @else Simples
                            @endif
                        </span>
                    </div>
                    @if($tipo === 'variacao')
                    <div class="flex justify-between">
                        <span class="text-indigo-200">Variações</span>
                        <span class="font-semibold">{{ count($variations) }}</span>
                    </div>
                    @elseif($tipo === 'composto')
                    <div class="flex justify-between">
                        <span class="text-indigo-200">Componentes</span>
                        <span class="font-semibold">{{ count($components) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dimensões - Full Width -->
        <div class="mt-6">
            <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-ruler text-slate-400"></i>
                    Dimensões e Peso
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Comprimento (cm)</label>
                        <input type="number" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Largura (cm)</label>
                        <input type="number" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Altura (cm)</label>
                        <input type="number" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Peso (g)</label>
                        <input type="number" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm" placeholder="0">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
