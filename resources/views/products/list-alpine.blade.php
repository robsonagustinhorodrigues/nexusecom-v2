<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - NexusEcom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
    </style>
</head>
<body class="bg-slate-900 text-white" x-data="products()" x-init="init()">

    <!-- Top Bar -->
    <header class="fixed top-0 left-0 right-0 h-14 bg-slate-900/95 backdrop-blur border-b border-slate-800 z-50 flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <a href="/dashboard-simple" class="p-2 hover:bg-slate-800 rounded-lg">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-bolt text-white text-sm"></i>
                </div>
                <span class="font-bold text-lg">Produtos</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <select x-model="empresaId" @change="loadProducts()" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm">
                <option value="4">MaxLider</option>
                <option value="5">LideraMais</option>
                <option value="6">LideraMix</option>
            </select>
            <a href="/products/create-alpine" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline">Novo</span>
            </a>
        </div>
    </header>

    <main class="lg:ml-64 pt-14 min-h-screen">
        <div class="p-6">
            
            <!-- Filters -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-4 mb-6">
                <div class="flex flex-wrap gap-3">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input 
                                type="text" 
                                x-model="search"
                                @input.debounce.300ms="loadProducts()"
                                placeholder="Buscar produtos..."
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                            >
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <select x-model="tipo" @change="loadProducts()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todos os tipos</option>
                        <option value="simples">Simples</option>
                        <option value="variacao">Com Variações</option>
                        <option value="composto">Kit / Composto</option>
                    </select>

                    <!-- Status Filter -->
                    <select x-model="status" @change="loadProducts()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        <option value="1">Ativos</option>
                        <option value="0">Inativos</option>
                    </select>

                    <!-- Per Page -->
                    <select x-model="perPage" @change="loadProducts()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                        <option value="20">20 por página</option>
                        <option value="50">50 por página</option>
                        <option value="100">100 por página</option>
                    </select>
                </div>
            </div>

            <!-- Loading -->
            <div x-show="loading" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
                <p class="text-slate-400 mt-2">Carregando...</p>
            </div>

            <!-- Products Grid -->
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <template x-for="product in products" :key="product.id">
                    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden hover:border-indigo-500/50 transition-all group">
                        <!-- Image -->
                        <div class="aspect-square bg-slate-700 relative">
                            <img x-show="product.foto_principal" :src="product.foto_principal" class="w-full h-full object-cover">
                            <div x-show="!product.foto_principal" class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-image text-3xl text-slate-600"></i>
                            </div>
                            
                            <!-- Type Badge -->
                            <div class="absolute top-2 left-2">
                                <span class="text-xs px-2 py-1 rounded-full font-medium"
                                    :class="{
                                        'bg-emerald-500/20 text-emerald-400': product.tipo === 'simples',
                                        'bg-amber-500/20 text-amber-400': product.tipo === 'variacao',
                                        'bg-purple-500/20 text-purple-400': product.tipo === 'composto'
                                    }"
                                    x-text="getTipoLabel(product.tipo)"
                                ></span>
                            </div>

                            <!-- Status Toggle -->
                            <button 
                                @click="toggleStatus(product)"
                                class="absolute top-2 right-2 w-8 h-8 rounded-full flex items-center justify-center transition-all"
                                :class="product.ativo ? 'bg-emerald-500' : 'bg-slate-600'"
                            >
                                <i class="fas text-white text-xs" :class="product.ativo ? 'fa-check' : 'fa-times'"></i>
                            </button>
                        </div>

                        <!-- Info -->
                        <div class="p-4">
                            <h3 class="font-semibold text-sm line-clamp-2 mb-2" x-text="product.nome"></h3>
                            
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs text-slate-400" x-text="'SKU: ' + (product.sku || '-')"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                    :class="product.ativo ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-600 text-slate-400'"
                                    x-text="product.ativo ? 'Ativo' : 'Inativo'"
                                ></span>
                            </div>

                            <div class="flex items-end justify-between">
                                <div>
                                    <p class="text-xs text-slate-500">Venda</p>
                                    <p class="text-lg font-bold text-emerald-400" x-text="formatMoney(product.preco_venda)"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-slate-500">Estoque</p>
                                    <p class="text-sm font-semibold" :class="product.estoque > 0 ? 'text-blue-400' : 'text-red-400'" x-text="product.estoque"></p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 mt-4 pt-3 border-t border-slate-700">
                                <a :href="'/products/edit-alpine?id=' + product.id" class="flex-1 bg-slate-700 hover:bg-slate-600 text-center py-2 rounded-lg text-xs font-medium transition">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <button @click="deleteProduct(product)" class="px-3 bg-slate-700 hover:bg-red-600 rounded-lg text-xs transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && products.length === 0" class="text-center py-12">
                <i class="fas fa-box text-4xl text-slate-600 mb-4"></i>
                <p class="text-slate-400">Nenhum produto encontrado</p>
                <a href="/products/create-alpine" class="text-indigo-400 hover:text-indigo-300 text-sm mt-2 inline-block">
                    + Criar primeiro produto
                </a>
            </div>

            <!-- Pagination -->
            <div x-show="totalPages > 1" class="flex items-center justify-between mt-6">
                <p class="text-sm text-slate-400">
                    Mostrando <span x-text="from"></span> - <span x-text="to"></span> de <span x-text="total"></span>
                </p>
                <div class="flex gap-2">
                    <button 
                        @click="prevPage()" 
                        :disabled="currentPage === 1"
                        class="px-3 py-1 bg-slate-800 border border-slate-700 rounded-lg text-sm disabled:opacity-50"
                    >
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <template x-for="page in visiblePages" :key="page">
                        <button 
                            @click="goToPage(page)"
                            class="px-3 py-1 rounded-lg text-sm"
                            :class="page === currentPage ? 'bg-indigo-600 text-white' : 'bg-slate-800 border border-slate-700 hover:bg-slate-700'"
                            x-text="page"
                        ></button>
                    </template>
                    
                    <button 
                        @click="nextPage()" 
                        :disabled="currentPage === totalPages"
                        class="px-3 py-1 bg-slate-800 border border-slate-700 rounded-lg text-sm disabled:opacity-50"
                    >
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

        </div>
    </main>

    <script>
    function products() {
        return {
            empresaId: '6',
            products: [],
            loading: false,
            
            // Filters
            search: '',
            tipo: '',
            status: '',
            perPage: 20,
            
            // Pagination
            currentPage: 1,
            totalPages: 1,
            total: 0,
            from: 0,
            to: 0,
            
            init() {
                this.loadProducts();
            },
            
            async loadProducts() {
                this.loading = true;
                
                const params = new URLSearchParams({
                    empresa_id: this.empresaId,
                    page: this.currentPage,
                    per_page: this.perPage,
                    search: this.search,
                    tipo: this.tipo,
                    status: this.status,
                });
                
                try {
                    const response = await fetch(`/api/products?${params}`);
                    const data = await response.json();
                    
                    this.products = data.data || [];
                    this.currentPage = data.current_page || 1;
                    this.totalPages = data.last_page || 1;
                    this.total = data.total || 0;
                    this.from = data.from || 0;
                    this.to = data.to || 0;
                } catch (e) {
                    console.error('Error loading products:', e);
                }
                
                this.loading = false;
            },
            
            get visiblePages() {
                const pages = [];
                const maxVisible = 5;
                let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
                let end = Math.min(this.totalPages, start + maxVisible - 1);
                
                if (end - start < maxVisible - 1) {
                    start = Math.max(1, end - maxVisible + 1);
                }
                
                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }
                return pages;
            },
            
            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.loadProducts();
                }
            },
            
            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.loadProducts();
                }
            },
            
            goToPage(page) {
                this.currentPage = page;
                this.loadProducts();
            },
            
            async toggleStatus(product) {
                try {
                    const response = await fetch(`/api/products/${product.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ ativo: !product.ativo })
                    });
                    
                    if (response.ok) {
                        product.ativo = !product.ativo;
                    }
                } catch (e) {
                    console.error('Error toggling status:', e);
                }
            },
            
            async deleteProduct(product) {
                if (!confirm('Tem certeza que deseja excluir este produto?')) return;
                
                try {
                    const response = await fetch(`/api/products/${product.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    
                    if (response.ok) {
                        this.products = this.products.filter(p => p.id !== product.id);
                    }
                } catch (e) {
                    console.error('Error deleting:', e);
                }
            },
            
            getTipoLabel(tipo) {
                const labels = {
                    'simples': 'Simples',
                    'variacao': 'Variação',
                    'composto': 'Kit'
                };
                return labels[tipo] || tipo;
            },
            
            formatMoney(value) {
                return new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(value || 0);
            }
        }
    }
    </script>
</body>
</html>
