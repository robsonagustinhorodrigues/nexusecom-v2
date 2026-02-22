@extends('layouts.alpine')

@section('title', 'Editar Produto - NexusEcom')
@section('header_title', 'Editar Produto')

@section('content')
<div x-data="productEdit()" x-init="init()" class="max-w-4xl mx-auto">
    <!-- Botão Voltar e Salvar -->
    <div class="flex items-center justify-between mb-6">
        <a href="/products" class="flex items-center gap-2 text-slate-400 hover:text-white transition">
            <i class="fas fa-arrow-left"></i>
            <span>Voltar para Lista</span>
        </a>
        <button 
            @click="save()" 
            :disabled="saving"
            class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 disabled:opacity-50"
        >
            <i x-show="saving" class="fas fa-spinner fa-spin"></i>
            <span x-text="saving ? 'Salvando...' : 'Salvar'"></span>
        </button>
    </div>
            
            <!-- Success/Error Messages -->
            <div x-show="successMessage" x-transition class="mb-4 p-4 bg-emerald-500/20 border border-emerald-500/50 rounded-lg text-emerald-400">
                <i class="fas fa-check-circle mr-2"></i>
                <span x-text="successMessage"></span>
            </div>

            <div x-show="errorMessage" x-transition class="mb-4 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-red-400">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span x-text="errorMessage"></span>
            </div>

            <!-- Loading -->
            <div x-show="loading" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-3xl text-indigo-500"></i>
            </div>

            <!-- Form -->
            <div x-show="!loading" class="space-y-6">
                
                <!-- Type Selector -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
                    <label class="block text-sm font-medium text-slate-400 mb-3">Tipo de Produto</label>
                    <div class="flex gap-3">
                        <button 
                            @click="product.tipo = 'simples'"
                            :class="product.tipo === 'simples' ? 'bg-indigo-600 border-indigo-500' : 'bg-slate-700 border-slate-600'"
                            class="px-4 py-2 rounded-lg border-2 font-medium transition flex items-center gap-2"
                        >
                            <i class="fas fa-box"></i> Simples
                        </button>
                        <button 
                            @click="product.tipo = 'variacao'"
                            :class="product.tipo === 'variacao' ? 'bg-indigo-600 border-indigo-500' : 'bg-slate-700 border-slate-600'"
                            class="px-4 py-2 rounded-lg border-2 font-medium transition flex items-center gap-2"
                        >
                            <i class="fas fa-layer-group"></i> Variações
                        </button>
                        <button 
                            @click="product.tipo = 'composto'"
                            :class="product.tipo === 'composto' ? 'bg-indigo-600 border-indigo-500' : 'bg-slate-700 border-slate-600'"
                            class="px-4 py-2 rounded-lg border-2 font-medium transition flex items-center gap-2"
                        >
                            <i class="fas fa-boxes"></i> Kit
                        </button>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <h2 class="font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-indigo-400"></i> Informações Básicas
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Nome *</label>
                            <input type="text" x-model="product.nome" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-slate-400 mb-1">SKU</label>
                                <input type="text" x-model="product.sku" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-1">EAN</label>
                                <input type="text" x-model="product.ean" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Marca</label>
                                <input type="text" x-model="product.marca" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-1">Categoria</label>
                                <select x-model="product.categoria_id" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                                    <option value="">Selecione...</option>
                                    <template x-for="cat in categorias" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.nome"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Descrição</label>
                            <textarea x-model="product.descricao" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Photos -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <h2 class="font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-images text-rose-400"></i> Fotos do Produto
                    </h2>
                    
                    <!-- Foto Principal -->
                    <div class="mb-4">
                        <label class="block text-sm text-slate-400 mb-2">Foto Principal</label>
                        <div class="flex items-center gap-4">
                            <div class="w-24 h-24 rounded-xl bg-slate-700 border-2 border-dashed border-slate-600 flex items-center justify-center overflow-hidden">
                                <img x-show="product.foto_principal" :src="product.foto_principal" class="w-full h-full object-cover">
                                <i x-show="!product.foto_principal" class="fas fa-image text-2xl text-slate-500"></i>
                            </div>
                            <div class="flex-1">
                                <input type="text" x-model="product.foto_principal" placeholder="URL da imagem..." 
                                    class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <p class="text-xs text-slate-500 mt-1">Cole a URL da imagem ou faça upload</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Galeria de Fotos -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm text-slate-400">Fotos Adicionais</label>
                            <span class="text-xs text-slate-500" x-text="'(' + product.fotos_galeria.length + '/10)'"></span>
                        </div>
                        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                            <!-- Adicionar foto -->
                            <label 
                                :class="product.fotos_galeria.length >= 10 ? 'opacity-30 cursor-not-allowed' : ''"
                                class="aspect-square rounded-xl border-2 border-dashed border-slate-600 flex flex-col items-center justify-center text-slate-500 hover:border-indigo-500 hover:text-indigo-500 cursor-pointer transition-all"
                            >
                                <i class="fas fa-plus text-xl mb-1"></i>
                                <span class="text-xs font-medium">Adicionar</span>
                                <input 
                                    type="text" 
                                    @change="addGalleryPhoto($event)" 
                                    placeholder="URL..." 
                                    :disabled="product.fotos_galeria.length >= 10"
                                    class="absolute opacity-0 w-full h-full cursor-pointer"
                                >
                            </label>
                            
                            <!-- Fotos existentes -->
                            <template x-for="(photo, index) in product.fotos_galeria" :key="index">
                                <div class="aspect-square rounded-xl bg-slate-700 border border-slate-600 relative group overflow-hidden">
                                    <img :src="photo" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                        <button @click="removeGalleryPhoto(index)" class="p-2 bg-red-500/20 rounded-lg text-red-400 hover:bg-red-500/30" title="Excluir">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                    <!-- Drag handle for reordering -->
                                    <div class="absolute bottom-1 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 cursor-move">
                                        <i class="fas fa-grip-lines text-white/50 text-xs"></i>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Arraste para reordenar. Máximo 10 fotos.</p>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <h2 class="font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-dollar-sign text-emerald-400"></i> Precificação
                    </h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Preço de Venda</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">R$</span>
                                <input type="number" step="0.01" x-model="product.preco_venda" class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 focus:border-indigo-500 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Custo Base</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">R$</span>
                                <input type="number" step="0.01" x-model="product.preco_custo" class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 focus:border-indigo-500 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">
                                <i class="fas fa-tag text-amber-500 mr-1"></i>Custo Adicional
                                <span class="text-xs">(etiqueta/embalagem)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">R$</span>
                                <input type="number" step="0.01" x-model="product.custo_adicional" class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 focus:border-amber-500 focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Custo Total</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">R$</span>
                                <input type="text" x-model="custoTotalDisplay" class="w-full bg-slate-800 border border-slate-600 rounded-lg pl-10 pr-4 py-2 text-white font-medium" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <h2 class="font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-box text-blue-400"></i> Estoque
                    </h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Quantidade</label>
                            <input type="number" x-model="product.estoque" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Unidade</label>
                            <select x-model="product.unidade_medida" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                                <option value="UN">Unidade</option>
                                <option value="KG">Kg</option>
                                <option value="LT">Litro</option>
                                <option value="MT">Metro</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Dimensions -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <h2 class="font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-ruler text-purple-400"></i> Dimensões
                    </h2>
                    
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Peso (g)</label>
                            <input type="number" x-model="product.peso" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Altura (cm)</label>
                            <input type="number" x-model="product.altura" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Largura (cm)</label>
                            <input type="number" x-model="product.largura" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Profund. (cm)</label>
                            <input type="number" x-model="product.profundidade" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Fiscal -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <h2 class="font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-file-invoice text-amber-400"></i> Informações Fiscais
                    </h2>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">NCM</label>
                            <input type="text" x-model="product.ncm" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">CEST</label>
                            <input type="text" x-model="product.cest" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Origem</label>
                            <select x-model="product.origem" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:border-indigo-500 focus:outline-none">
                                <option value="0">Nacional</option>
                                <option value="1">Importada</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Variations -->
                <div x-show="product.tipo === 'variacao'" class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-bold flex items-center gap-2">
                            <i class="fas fa-layer-group text-amber-400"></i> Variações
                        </h2>
                        <button @click="addVariation()" class="px-3 py-1 bg-indigo-600 rounded-lg text-sm">
                            <i class="fas fa-plus"></i> Adicionar
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <template x-for="(variation, index) in variations" :key="index">
                            <div class="flex gap-3 items-center p-3 bg-slate-700 rounded-lg">
                                <div class="flex-1">
                                    <input type="text" x-model="variation.label" placeholder="Nome (ex: P, M, G)" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm">
                                </div>
                                <div class="w-24">
                                    <input type="text" x-model="variation.sku" placeholder="SKU" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm">
                                </div>
                                <div class="w-28">
                                    <input type="number" step="0.01" x-model="variation.preco_venda" placeholder="Preço" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm">
                                </div>
                                <div class="w-20">
                                    <input type="number" x-model="variation.estoque" placeholder="Estoque" class="w-full bg-slate-900 border border-slate-600 rounded px-3 py-2 text-sm">
                                </div>
                                <button @click="removeVariation(index)" class="p-2 text-red-400 hover:text-red-300">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                        
                        <div x-show="variations.length === 0" class="text-center py-6 text-slate-500">
                            <i class="fas fa-layer-group text-2xl mb-2 opacity-50"></i>
                            <p>Nenhuma variação</p>
                        </div>
                    </div>
                </div>

                <!-- Compound -->
                <div x-show="product.tipo === 'composto'" class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-bold flex items-center gap-2">
                            <i class="fas fa-boxes text-emerald-400"></i> Componentes do Kit
                        </h2>
                        <span class="text-sm text-slate-400">Estoque máx: <span class="text-emerald-400 font-bold" x-text="maxCompoundStock"></span></span>
                    </div>
                    
                    <!-- Search -->
                    <div class="mb-4 relative">
                        <input 
                            type="text" 
                            x-model="componentSearch"
                            @keyup.debounce.300ms="searchComponents()"
                            placeholder="Buscar produto por nome ou SKU..."
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-2"
                        >
                        <div x-show="componentResults.length > 0" class="absolute z-10 w-full mt-1 bg-slate-800 border rounded-lg shadow-lg max-h-48 overflow-auto">
                            <template x-for="result in componentResults" :key="result.id">
                                <button 
                                    @click="addComponent(result)"
                                    class="w-full text-left px-4 py-2 hover:bg-slate-700 border-b border-slate-700 last:border-0"
                                >
                                    <div class="font-medium" x-text="result.nome"></div>
                                    <div class="text-xs text-slate-400">
                                        SKU: <span x-text="result.sku"></span> | R$ <span x-text="result.preco_venda"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <!-- List -->
                    <div class="space-y-2">
                        <template x-for="(comp, index) in components" :key="index">
                            <div class="flex gap-3 items-center p-3 bg-slate-700 rounded-lg">
                                <div class="flex-1">
                                    <div class="font-medium" x-text="comp.nome"></div>
                                    <div class="text-xs text-slate-400">SKU: <span x-text="comp.sku"></span></div>
                                </div>
                                <div class="w-16">
                                    <input type="number" min="1" x-model="comp.quantity" class="w-full bg-slate-900 border border-slate-600 rounded px-2 py-1 text-sm">
                                </div>
                                <div class="w-24 text-right text-emerald-400 font-medium">
                                    R$ <span x-text="(comp.unit_price * comp.quantity).toFixed(2)"></span>
                                </div>
                                <button @click="removeComponent(index)" class="p-2 text-red-400 hover:text-red-300">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                        
                        <div x-show="components.length > 0" class="text-right p-3 bg-emerald-500/10 rounded-lg font-bold text-emerald-400">
                            Total: R$ <span x-text="componentsTotal.toFixed(2)"></span>
                        </div>
                        
                        <div x-show="components.length === 0" class="text-center py-6 text-slate-500">
                            <i class="fas fa-boxes text-2xl mb-2 opacity-50"></i>
                            <p>Nenhum componente</p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="product.ativo" class="w-5 h-5 rounded text-indigo-600">
                        <span class="font-medium" x-text="product.ativo ? 'Produto Ativo' : 'Produto Inativo'"></span>
                    </label>
                </div>

            </div>
        </div>
    </main>

    <script>
    function productEdit() {
        return {
            productId: null,
            loading: true,
            saving: false,
            successMessage: '',
            errorMessage: '',
            custoTotalDisplay: 'R$ 0,00',
            
            product: {
                nome: '',
                sku: '',
                ean: '',
                marca: '',
                descricao: '',
                tipo: 'simples',
                categoria_id: '',
                preco_venda: 0,
                preco_custo: 0,
                custo_adicional: 0,
                estoque: 0,
                unidade_medida: 'UN',
                peso: 0,
                altura: 0,
                largura: 0,
                profundidade: 0,
                ncm: '',
                cest: '',
                origem: '0',
                ativo: true,
                foto_principal: '',
                fotos_galeria: [],
            },
            
            variations: [],
            components: [],
            
            // Compound search
            componentSearch: '',
            componentResults: [],
            
            // Data
            categorias: [],
            
            init() {
                // Get product ID from URL
                const urlParams = new URLSearchParams(window.location.search);
                this.productId = urlParams.get('id');
                
                this.loadCategorias();
                
                // Watch for custo changes
                this.$watch('product.preco_custo', () => this.updateCustoTotal());
                this.$watch('product.custo_adicional', () => this.updateCustoTotal());
                
                if (this.productId) {
                    this.loadProduct();
                } else {
                    this.loading = false;
                }
            },
            
            updateCustoTotal() {
                const custo = (parseFloat(this.product.preco_custo) || 0) + (parseFloat(this.product.custo_adicional) || 0);
                this.custoTotalDisplay = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(custo);
            },
            
            async loadCategorias() {
                try {
                    const response = await fetch('/api/categorias');
                    this.categorias = await response.json();
                } catch (e) {
                    console.error('Error loading categories:', e);
                }
            },
            
            async loadProduct() {
                try {
                    const response = await fetch(`/api/products/${this.productId}`);
                    const data = await response.json();
                    
                    this.product = {
                        nome: data.nome || '',
                        sku: data.sku || '',
                        ean: data.ean || '',
                        marca: data.marca || '',
                        descricao: data.descricao || '',
                        tipo: data.tipo || 'simples',
                        categoria_id: data.categoria_id || '',
                        preco_venda: data.preco_venda || 0,
                        preco_custo: data.preco_custo || 0,
                        custo_adicional: data.custo_adicional || 0,
                        estoque: data.estoque || 0,
                        unidade_medida: data.unidade_medida || 'UN',
                        peso: data.peso || 0,
                        altura: data.altura || 0,
                        largura: data.largura || 0,
                        profundidade: data.profundidade || 0,
                        ncm: data.ncm || '',
                        cest: data.cest || '',
                        origem: data.origem || '0',
                        ativo: data.ativo !== false,
                        foto_principal: data.foto_principal || '',
                        fotos_galeria: data.fotos_galeria || [],
                    };
                    
                    // Update custo total display
                    this.updateCustoTotal();
                    
                    // Load variations (as child products)
                    if (data.variations) {
                        this.variations = data.variations.map(v => ({
                            label: v.variation_color || v.variation_size || 'Variação',
                            sku: v.sku || '',
                            preco_venda: v.preco_venda,
                            preco_custo: v.preco_custo || 0,
                            estoque: v.estoque || 0,
                        }));
                    }
                    
                    // Load components
                    if (data.components) {
                        this.components = data.components.map(c => ({
                            product_id: c.component_product_id,
                            nome: c.component_product?.nome || 'Produto',
                            sku: c.component_product?.sku || '',
                            quantity: c.quantity,
                            unit_price: c.unit_price,
                            preco_venda: c.component_product?.preco_venda || 0,
                        }));
                    }
                    
                } catch (e) {
                    console.error('Error loading product:', e);
                    this.errorMessage = 'Erro ao carregar produto';
                }
                
                this.loading = false;
            },
            
            // Variations
            addVariation() {
                this.variations.push({
                    label: '',
                    sku: '',
                    preco_venda: this.product.preco_venda,
                    preco_custo: this.product.preco_custo,
                    estoque: 0,
                });
            },
            
            removeVariation(index) {
                this.variations.splice(index, 1);
            },
            
            // Compound
            async searchComponents() {
                if (this.componentSearch.length < 2) {
                    this.componentResults = [];
                    return;
                }
                
                try {
                    const response = await fetch(`/api/products/search?q=${encodeURIComponent(this.componentSearch)}`);
                    this.componentResults = await response.json();
                } catch (e) {
                    console.error('Error searching:', e);
                }
            },
            
            addComponent(product) {
                if (this.components.find(c => c.product_id === product.id)) return;
                
                this.components.push({
                    product_id: product.id,
                    nome: product.nome,
                    sku: product.sku,
                    quantity: 1,
                    unit_price: product.preco_venda,
                    preco_venda: product.preco_venda,
                });
                
                this.componentSearch = '';
                this.componentResults = [];
                
                this.product.preco_venda = this.componentsTotal;
            },
            
            removeComponent(index) {
                this.components.splice(index, 1);
                this.product.preco_venda = this.componentsTotal;
            },
            
            get componentsTotal() {
                return this.components.reduce((sum, c) => sum + (c.unit_price * c.quantity), 0);
            },
            
            get maxCompoundStock() {
                if (this.components.length === 0) return 0;
                // Simplified - in real app would check actual stock
                return 0;
            },
            
            async save() {
                this.saving = true;
                this.successMessage = '';
                this.errorMessage = '';
                
                try {
                    const response = await fetch(`/api/products/${this.productId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            ...this.product,
                            variations: this.variations,
                            components: this.components
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.successMessage = data.message;
                        setTimeout(() => {
                            window.location.href = '/products';
                        }, 1500);
                    } else {
                        this.errorMessage = data.message || 'Erro ao salvar';
                    }
                    
                } catch (e) {
                    console.error('Error saving:', e);
                    this.errorMessage = 'Erro ao salvar produto';
                }
                
                this.saving = false;
            },
            
            // Photo functions
            addGalleryPhoto(event) {
                const url = event.target.value;
                if (url && url.trim()) {
                    if (this.product.fotos_galeria.length >= 10) {
                        alert('Máximo de 10 fotos permitido!');
                        return;
                    }
                    this.product.fotos_galeria.push(url.trim());
                    event.target.value = '';
                }
            },
            
            removeGalleryPhoto(index) {
                this.product.fotos_galeria.splice(index, 1);
            },
            
            formatMoney(value) {
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
            }
        }
    }
    </script>
@endsection
