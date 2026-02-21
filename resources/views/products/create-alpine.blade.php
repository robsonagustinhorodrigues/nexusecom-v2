<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produtos - NexusEcom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50" x-data="productForm()">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="/products" class="text-gray-500 hover:text-gray-700">
            <!-- User Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 pl-3 border-l border-gray-300 pr-2 py-1 rounded-lg hover:bg-gray-100 transition">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-bold text-white">R</div>
                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-50">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-900">Robson</p>
                        <p class="text-xs text-gray-500">robson@email.com</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-red-600 flex items-center gap-2">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </button>
                    </form>
                </div>
            </div>
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-900">
                    <span x-text="editMode ? 'Editar Produto' : 'Novo Produto'"></span>
                </h1>
            </div>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 hover:text-red-600" title="Sair">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
                <button 
                    @click="save()" 
                    :disabled="saving"
                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span x-show="!saving"><i class="fas fa-save mr-2"></i>Salvar</span>
                    <span x-show="saving"><i class="fas fa-spinner fa-spin mr-2"></i>Salvando...</span>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
        
        <!-- Success Message -->
        <div x-show="successMessage" x-transition class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <span x-text="successMessage"></span>
        </div>

        <!-- Error Message -->
        <div x-show="errorMessage" x-transition class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <span x-text="errorMessage"></span>
        </div>

        <!-- Type Selector -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de Produto</label>
            <div class="flex gap-3">
                <button 
                    @click="product.tipo = 'simples'"
                    :class="product.tipo === 'simples' ? 'bg-indigo-100 border-indigo-500 text-indigo-700' : 'bg-gray-50 border-gray-200'"
                    class="px-4 py-3 rounded-lg border-2 font-medium transition"
                >
                    <i class="fas fa-box mr-2"></i>Produto Simples
                </button>
                <button 
                    @click="product.tipo = 'variacao'"
                    :class="product.tipo === 'variacao' ? 'bg-indigo-100 border-indigo-500 text-indigo-700' : 'bg-gray-50 border-gray-200'"
                    class="px-4 py-3 rounded-lg border-2 font-medium transition"
                >
                    <i class="fas fa-layer-group mr-2"></i>Com Variações
                </button>
                <button 
                    @click="product.tipo = 'composto'"
                    :class="product.tipo === 'composto' ? 'bg-indigo-100 border-indigo-500 text-indigo-700' : 'bg-gray-50 border-gray-200'"
                    class="px-4 py-3 rounded-lg border-2 font-medium transition"
                >
                    <i class="fas fa-boxes mr-2"></i>Kit / Composto
                </button>
            </div>
        </div>

        <!-- Main Form -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column - Basic Info -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Basic Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-indigo-500 mr-2"></i>Informações Básicas
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto *</label>
                            <input 
                                type="text" 
                                x-model="product.nome"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Digite o nome do produto"
                            >
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                                <input 
                                    type="text" 
                                    x-model="product.sku"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Código interno"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">EAN</label>
                                <input 
                                    type="text" 
                                    x-model="product.ean"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Código de barras"
                                >
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                <input 
                                    type="text" 
                                    x-model="product.marca"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Marca"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                                <select 
                                    x-model="product.categoria_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">Selecione...</option>
                                    <template x-for="cat in categorias" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.nome"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                            <textarea 
                                x-model="product.descricao"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                placeholder="Descrição do produto"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Pricing Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-dollar-sign text-emerald-500 mr-2"></i>Precificação
                    </h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Preço de Venda</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">R$</span>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    x-model="product.preco_venda"
                                    class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Custo Base</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">R$</span>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    x-model="product.preco_custo"
                                    class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-tag text-amber-500 mr-1"></i>Custo Adicional
                                <span class="text-xs text-gray-500">(etiqueta/embalagem)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">R$</span>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    x-model="product.custo_adicional"
                                    class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
                                    placeholder="0,00"
                                >
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Custo Total</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">R$</span>
                                <input 
                                    type="text"
                                    :value="formatMoney((parseFloat(product.preco_custo) || 0) + (parseFloat(product.custo_adicional) || 0))"
                                    class="w-full pl-8 pr-4 py-2 border border-gray-200 rounded-lg bg-gray-100 text-gray-600"
                                    readonly
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dimensions Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-ruler text-blue-500 mr-2"></i>Dimensões
                    </h2>
                    
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Peso (g)</label>
                            <input type="number" x-model="product.peso" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Altura (cm)</label>
                            <input type="number" x-model="product.altura" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Largura (cm)</label>
                            <input type="number" x-model="product.largura" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profund. (cm)</label>
                            <input type="number" x-model="product.profundidade" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </div>

                <!-- Fiscal Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-file-invoice text-orange-500 mr-2"></i>Informações Fiscais
                    </h2>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NCM</label>
                            <input type="text" x-model="product.ncm" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="0000.00.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEST</label>
                            <input type="text" x-model="product.cest" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="000.000.000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
                            <select x-model="product.origem" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="0">Nacional</option>
                                <option value="1">Estrangeira - Importação direta</option>
                                <option value="2">Estrangeira - Adquirida no mercado interno</option>
                                <option value="3">Nacional - Conteúdo de importação >= 40%</option>
                                <option value="4">Nacional - Básica</option>
                                <option value="5">Nacional - Conteúdo de importação < 40%</option>
                                <option value="6">Estrangeira - Importação direta sem similar nacional</option>
                                <option value="7">Estrangeira - Adquirida no mercado interno sem similar nacional</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Variations Section -->
                <div x-show="product.tipo === 'variacao'" x-transition class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-layer-group text-amber-500 mr-2"></i>Variações
                        </h2>
                        <button @click="addVariation()" class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                            <i class="fas fa-plus mr-1"></i>Adicionar
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <template x-for="(variation, index) in variations" :key="index">
                            <div class="flex gap-3 items-end p-3 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500">Nome</label>
                                    <input type="text" x-model="variation.label" class="w-full px-3 py-2 border rounded-lg" placeholder="Ex: P, M, G">
                                </div>
                                <div class="w-32">
                                    <label class="block text-xs text-gray-500">SKU</label>
                                    <input type="text" x-model="variation.sku" class="w-full px-3 py-2 border rounded-lg">
                                </div>
                                <div class="w-28">
                                    <label class="block text-xs text-gray-500">Preço</label>
                                    <input type="number" step="0.01" x-model="variation.preco_venda" class="w-full px-3 py-2 border rounded-lg">
                                </div>
                                <div class="w-24">
                                    <label class="block text-xs text-gray-500">Estoque</label>
                                    <input type="number" x-model="variation.estoque" class="w-full px-3 py-2 border rounded-lg">
                                </div>
                                <button @click="removeVariation(index)" class="p-2 text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                        
                        <div x-show="variations.length === 0" class="text-center py-8 text-gray-500">
                            <i class="fas fa-layer-group text-3xl mb-2 opacity-50"></i>
                            <p>Nenhuma variação adicionada</p>
                        </div>
                    </div>
                </div>

                <!-- Compound Section -->
                <div x-show="product.tipo === 'composto'" x-transition class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-boxes text-emerald-500 mr-2"></i>Componentes do Kit
                        </h2>
                        <span class="text-sm text-gray-500">
                            Estoque máx: <span x-text="maxCompoundStock" class="font-bold text-emerald-600"></span> kits
                        </span>
                    </div>
                    
                    <!-- Search -->
                    <div class="mb-4 relative">
                        <input 
                            type="text" 
                            x-model="componentSearch"
                            @keyup.debounce.300ms="searchComponents()"
                            placeholder="Buscar produto por nome ou SKU..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                        >
                        <div x-show="componentResults.length > 0" class="absolute z-10 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-auto">
                            <template x-for="result in componentResults" :key="result.id">
                                <button 
                                    @click="addComponent(result)"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-50 border-b last:border-0"
                                >
                                    <div class="font-medium" x-text="result.nome"></div>
                                    <div class="text-xs text-gray-500">
                                        SKU: <span x-text="result.sku"></span> | 
                                        R$ <span x-text="result.preco_venda"></span> | 
                                        Estoque: <span x-text="result.estoque"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Components List -->
                    <div class="space-y-2">
                        <template x-for="(comp, index) in components" :key="index">
                            <div class="flex gap-3 items-center p-3 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <div class="font-medium" x-text="comp.nome"></div>
                                    <div class="text-xs text-gray-500">SKU: <span x-text="comp.sku"></span></div>
                                </div>
                                <div class="w-20">
                                    <label class="block text-xs text-gray-500">Qtd</label>
                                    <input type="number" min="1" x-model="comp.quantity" class="w-full px-2 py-1 border rounded">
                                </div>
                                <div class="w-28">
                                    <label class="block text-xs text-gray-500">Preço Unit.</label>
                                    <input type="number" step="0.01" x-model="comp.unit_price" class="w-full px-2 py-1 border rounded">
                                </div>
                                <div class="w-24 text-right font-medium text-emerald-600">
                                    R$ <span x-text="(comp.unit_price * comp.quantity).toFixed(2)"></span>
                                </div>
                                <button @click="removeComponent(index)" class="p-2 text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                        
                        <div x-show="components.length > 0" class="text-right p-3 bg-emerald-50 rounded-lg font-bold text-emerald-700">
                            Total: R$ <span x-text="componentsTotal.toFixed(2)"></span>
                        </div>
                        
                        <div x-show="components.length === 0" class="text-center py-8 text-gray-500">
                            <i class="fas fa-boxes text-3xl mb-2 opacity-50"></i>
                            <p>Nenhum componente adicionado</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                
                <!-- Status Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Status</h2>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="product.ativo" class="w-5 h-5 text-indigo-600 rounded">
                        <span class="font-medium" x-text="product.ativo ? 'Ativo' : 'Inativo'"></span>
                    </label>
                </div>

                <!-- Quick Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Resumo</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tipo</span>
                            <span class="font-medium" x-text="tipoLabel"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Variações</span>
                            <span class="font-medium" x-text="variations.length"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Componentes</span>
                            <span class="font-medium" x-text="components.length"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <script>
    function productForm() {
        return {
            productId: null,
            editMode: false,
            saving: false,
            successMessage: '',
            errorMessage: '',
            
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
                peso: 0,
                altura: 0,
                largura: 0,
                profundidade: 0,
                ncm: '',
                cest: '',
                origem: '0',
                estoque: 0,
                ativo: true,
            },
            
            variations: [],
            components: [],
            
            // Compound search
            componentSearch: '',
            componentResults: [],
            
            // Data
            categorias: [],
            fornecedores: [],
            tags: [],
            
            init() {
                this.loadCategorias();
                
                // Check if editing
                const urlParams = new URLSearchParams(window.location.search);
                const id = urlParams.get('id');
                if (id) {
                    this.productId = id;
                    this.editMode = true;
                    this.loadProduct(id);
                }
            },
            
            async loadCategorias() {
                try {
                    const response = await fetch('/api/categorias', {
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    this.categorias = await response.json();
                } catch (e) {
                    console.error('Error loading categorias:', e);
                }
            },
            
            async loadProduct(id) {
                try {
                    const response = await fetch(`/api/products/${id}`, {
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    const data = await response.json();
                    
                    this.product = {
                        nome: data.nome,
                        sku: data.sku || '',
                        ean: data.ean || '',
                        marca: data.marca || '',
                        descricao: data.descricao || '',
                        tipo: data.tipo,
                        categoria_id: data.categoria_id,
                        preco_venda: data.preco_venda,
                        preco_custo: data.preco_custo,
                        peso: data.peso || 0,
                        altura: data.altura || 0,
                        largura: data.largura || 0,
                        profundidade: data.profundidade || 0,
                        ncm: data.ncm || '',
                        cest: data.cest || '',
                        origem: data.origem || '0',
                        estoque: data.estoque || 0,
                        ativo: data.ativo,
                    };
                    
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
            
            // Compound Components
            async searchComponents() {
                if (this.componentSearch.length < 2) {
                    this.componentResults = [];
                    return;
                }
                
                try {
                    const response = await fetch(`/api/products/search?q=${encodeURIComponent(this.componentSearch)}`, {
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    this.componentResults = await response.json();
                } catch (e) {
                    console.error('Error searching:', e);
                }
            },
            
            addComponent(product) {
                // Check if already added
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
                
                // Update kit price
                this.product.preco_venda = this.componentsTotal;
            },
            
            removeComponent(index) {
                this.components.splice(index, 1);
                this.product.preco_venda = this.componentsTotal;
            },
            
            // Computed
            get tipoLabel() {
                const labels = {
                    'simples': 'Simples',
                    'variacao': 'Com Variações',
                    'composto': 'Kit / Composto'
                };
                return labels[this.product.tipo] || 'Simples';
            },
            
            get componentsTotal() {
                return this.components.reduce((sum, c) => sum + (c.unit_price * c.quantity), 0);
            },
            
            get maxCompoundStock() {
                if (this.components.length === 0) return 0;
                
                let max = Infinity;
                this.components.forEach(c => {
                    const possible = Math.floor((c.preco_venda || 0) / (c.quantity || 1));
                    // We don't have real stock here, so return 0 for now
                });
                return max === Infinity ? 0 : max;
            },
            
            // Save
            async save() {
                this.saving = true;
                this.successMessage = '';
                this.errorMessage = '';
                
                try {
                    const url = this.editMode ? `/api/products/${this.productId}` : '/api/products';
                    const method = this.editMode ? 'PUT' : 'POST';
                    
                    const response = await fetch(url, {
                        method: method,
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
            }
        }
    }
    </script>
</body>
</html>
