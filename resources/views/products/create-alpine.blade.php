@extends('layouts.alpine')

@section('title', 'Cadastro de Produtos - NexusEcom')
@section('header_title', 'Produtos')

@section('content')
<div x-data="productForm()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="/products" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-xl text-slate-400 hover:text-white transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">
                    <span x-text="editMode ? 'Editar Produto' : 'Novo Produto'"></span>
                </h2>
                <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Cadastre e gerencie seus produtos</p>
            </div>
        </div>
        <button 
            @click="save()" 
            :disabled="saving"
            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-black italic rounded-xl disabled:opacity-50 transition"
        >
            <span x-show="!saving"><i class="fas fa-save mr-2"></i>Salvar</span>
            <span x-show="saving"><i class="fas fa-spinner fa-spin mr-2"></i>Salvando...</span>
        </button>
    </div>

    <!-- Messages -->
    <div x-show="successMessage" x-transition class="p-4 bg-emerald-500/20 border border-emerald-500/50 text-emerald-400 rounded-xl">
        <span x-text="successMessage"></span>
    </div>
    <div x-show="errorMessage" x-transition class="p-4 bg-rose-500/20 border border-rose-500/50 text-rose-400 rounded-xl">
        <span x-text="errorMessage"></span>
    </div>

    <!-- Type Selector -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
        <label class="block text-sm font-bold text-slate-400 mb-3 uppercase italic">Tipo de Produto</label>
        <div class="flex gap-3">
            <button 
                @click="product.tipo = 'simples'"
                :class="product.tipo === 'simples' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-700 border-slate-600 text-slate-400'"
                class="px-4 py-3 rounded-lg border-2 font-bold italic transition"
            >
                <i class="fas fa-box mr-2"></i>Produto Simples
            </button>
            <button 
                @click="product.tipo = 'variacao'"
                :class="product.tipo === 'variacao' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-700 border-slate-600 text-slate-400'"
                class="px-4 py-3 rounded-lg border-2 font-bold italic transition"
            >
                <i class="fas fa-layer-group mr-2"></i>Com Variações
            </button>
            <button 
                @click="product.tipo = 'composto'"
                :class="product.tipo === 'composto' ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-700 border-slate-600 text-slate-400'"
                class="px-4 py-3 rounded-lg border-2 font-bold italic transition"
            >
                <i class="fas fa-boxes mr-2"></i>Kit / Composto
            </button>
        </div>
    </div>

    <!-- Main Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Basic Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h3 class="text-lg font-black text-white uppercase italic mb-4">Informações Básicas</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Nome do Produto</label>
                        <input type="text" x-model="product.nome" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="Nome do produto">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">SKU</label>
                        <input type="text" x-model="product.sku" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="SKU">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">EAN</label>
                        <input type="text" x-model="product.ean" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="Código de barras">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Marca</label>
                        <input type="text" x-model="product.marca" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="Marca">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Categoria</label>
                        <select x-model="product.categoria_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none">
                            <option value="">Selecione...</option>
                            <template x-for="cat in categorias" :key="cat.id">
                                <option :value="cat.id" x-text="cat.nome"></option>
                            </template>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Descrição</label>
                        <textarea x-model="product.descricao" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="Descrição do produto"></textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h3 class="text-lg font-black text-white uppercase italic mb-4">Precificação</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Preço de Venda</label>
                        <input type="number" step="0.01" x-model="product.preco_venda" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="0,00">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Preço de Custo</label>
                        <input type="number" step="0.01" x-model="product.preco_custo" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="0,00">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Custo Adicional</label>
                        <input type="number" step="0.01" x-model="product.custo_adicional" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="0,00">
                    </div>
                </div>
            </div>

            <!-- Dimensions -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h3 class="text-lg font-black text-white uppercase italic mb-4">Dimensões e Peso</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Peso (kg)</label>
                        <input type="number" step="0.001" x-model="product.peso" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Altura (cm)</label>
                        <input type="number" step="0.01" x-model="product.altura" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Largura (cm)</label>
                        <input type="number" step="0.01" x-model="product.largura" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Profundidade (cm)</label>
                        <input type="number" step="0.01" x-model="product.profundidade" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="0">
                    </div>
                </div>
            </div>

            <!-- Fiscal -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h3 class="text-lg font-black text-white uppercase italic mb-4">Informações Fiscais</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">NCM</label>
                        <input type="text" x-model="product.ncm" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="NCM">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">CEST</label>
                        <input type="text" x-model="product.cest" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none" placeholder="CEST">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase italic mb-2">Origem</label>
                        <select x-model="product.origem" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white font-bold italic focus:ring-indigo-500 outline-none">
                            <option value="0">Nacional</option>
                            <option value="1">Estrangeira - Importação direta</option>
                            <option value="2">Estrangeira - Adquirida no mercado interno</option>
                            <option value="3">Nacional - Conteúdo de importação > 40%</option>
                            <option value="4">Nacional - Produção Própria</option>
                            <option value="5">Nacional - Conteúdo de importação <= 40%</option>
                            <option value="6">Estrangeira - Importação direta com contenido nacional > 0%</option>
                            <option value="7">Estrangeira - Adquirida mercado interno com contenido nacional > 0%</option>
                            <option value="8">Nacional - Conteúdo de importação > 70%</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" x-model="product.ativo" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="ml-3 text-sm font-bold text-white uppercase italic">Produto Ativo</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Variations -->
            <div x-show="product.tipo === 'variacao'" class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-black text-white uppercase italic">Variações</h3>
                    <button @click="addVariation()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold italic rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Adicionar Variação
                    </button>
                </div>
                <div class="space-y-4">
                    <template x-for="(variation, index) in variations" :key="index">
                        <div class="bg-slate-900 rounded-lg p-4 border border-slate-700">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-sm font-bold text-slate-400">Variação <span x-text="index + 1"></span></span>
                                <button @click="removeVariation(index)" class="text-rose-400 hover:text-rose-300">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                                <input type="text" x-model="variation.nome" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm" placeholder="Nome (ex: Vermelho, P, G)">
                                <input type="text" x-model="variation.sku" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm" placeholder="SKU (obrigatório)">
                                <input type="text" x-model="variation.color" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm" placeholder="Cor (opcional)">
                                <input type="text" x-model="variation.size" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm" placeholder="Tamanho (opcional)">
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="variation.herdar" class="w-4 h-4 rounded text-indigo-600">
                                    <span class="text-sm text-slate-400">Herdar atributos do pai</span>
                                </label>
                            </div>
                            <div x-show="!variation.herdar" class="grid grid-cols-2 gap-3 mt-3 pt-3 border-t border-slate-700">
                                <input type="number" step="0.01" x-model="variation.preco_venda" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm" placeholder="Preço de Venda">
                                <input type="number" step="0.01" x-model="variation.preco_custo" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm" placeholder="Preço de Custo">
                            </div>
                        </div>
                    </template>
                    <div x-show="variations.length === 0" class="text-center py-8 text-slate-500">
                        <i class="fas fa-layer-group text-3xl mb-2"></i>
                        <p class="text-sm">Nenhuma variação adicionada</p>
                    </div>
                </div>
            </div>

            <!-- Compound -->
            <div x-show="product.tipo === 'composto'" class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-black text-white uppercase italic">Componentes do Kit</h3>
                    <button @click="searchComponent()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold italic rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Adicionar Componente
                    </button>
                </div>
                <div class="space-y-4">
                    <template x-for="(component, index) in components" :key="index">
                        <div class="bg-slate-900 rounded-lg p-4 border border-slate-700 flex items-center justify-between">
                            <div>
                                <p class="text-white font-bold" x-text="component.nome"></p>
                                <p class="text-sm text-slate-400">SKU: <span x-text="component.sku"></span></p>
                            </div>
                            <div class="flex items-center gap-4">
                                <input type="number" x-model="component.quantity" class="w-20 bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm text-center" placeholder="Qtd">
                                <button @click="removeComponent(index)" class="text-rose-400 hover:text-rose-300">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="components.length === 0" class="text-center py-8 text-slate-500">
                        <i class="fas fa-boxes text-3xl mb-2"></i>
                        <p class="text-sm">Nenhum componente adicionado</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Image -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h3 class="text-lg font-black text-white uppercase italic mb-4">Imagem</h3>
                <label class="border-2 border-dashed border-slate-600 rounded-xl p-8 flex flex-col items-center cursor-pointer hover:border-indigo-500 transition">
                    <i class="fas fa-cloud-upload-alt text-3xl text-slate-500 mb-2"></i>
                    <span class="text-sm text-slate-400">Clique para上传 imagem</span>
                    <input type="file" accept="image/*" @change="handleImageUpload($event)" class="hidden">
                </label>
                <div x-show="product.imagem" class="mt-4">
                    <img :src="product.imagem" class="w-full rounded-lg">
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
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
            ativo: true,
            imagem: null,
        },
        
        variations: [],
        components: [],
        
        categorias: [],
        empresaId: 6,
        
        async init() {
            this.empresaId = localStorage.getItem('empresa_id') || 6;
            await this.loadCategorias();
            
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');
            if (id) {
                this.productId = id;
                this.editMode = true;
                await this.loadProduct(id);
            }
        },
        
        async loadCategorias() {
            try {
                const res = await fetch('/api/products/categorias');
                if (res.ok) {
                    this.categorias = await res.json();
                }
            } catch (e) {
                console.error('Erro ao carregar categorias:', e);
            }
        },
        
        async loadProduct(id) {
            try {
                const res = await fetch(`/api/products/${id}`);
                if (res.ok) {
                    const data = await res.json();
                    this.product = { 
                        ...this.product, 
                        ...data,
                        tipo: data.tipo || 'simples'
                    };
                    this.variations = data.variations || [];
                    this.components = data.components || [];
                    console.log('Produto carregado:', this.product);
                    console.log('Variações carregadas:', this.variations);
                }
            } catch (e) {
                console.error('Erro ao carregar produto:', e);
            }
        },
        
        addVariation() {
            this.variations.push({ 
                nome: '', 
                sku: '', 
                color: '', 
                size: '', 
                herdar: true,
                preco_venda: 0,
                preco_custo: 0
            });
        },
        
        removeVariation(index) {
            this.variations.splice(index, 1);
        },
        
        addComponent() {
            // Component adding logic
        },
        
        removeComponent(index) {
            this.components.splice(index, 1);
        },
        
        async handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.product.imagem = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        
        async save() {
            this.saving = true;
            this.successMessage = '';
            this.errorMessage = '';
            
            const empresaId = this.empresaId || localStorage.getItem('empresa_id') || 6;
            
            try {
                const url = this.editMode ? `/api/products/${this.productId}` : '/api/products';
                const method = this.editMode ? 'PUT' : 'POST';
                
                const payload = {
                    ...this.product,
                    empresa: empresaId,
                    variations: this.variations,
                    components: this.components
                };
                console.log('Salvando produto:', payload);
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
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
@endsection
