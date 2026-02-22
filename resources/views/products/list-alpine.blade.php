@extends('layouts.alpine')

@section('title', 'Produtos - NexusEcom')
@section('header_title', 'Produtos')

@section('content')
<div x-data="productsPage()" x-init="init()">
    <!-- Header com Filtros -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Catálogo de Produtos</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Gestão Centralizada de SKUs & Variações</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 bg-slate-800 p-2 rounded-2xl border border-slate-700 shadow-lg">
            <div class="relative w-64 border-r border-slate-700 pr-3">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                <input type="text" x-model="search" @input.debounce.300ms="currentPage = 1; loadProducts()" placeholder="Buscar SKU ou Nome..." 
                       class="w-full bg-slate-900 border-none rounded-xl pl-9 pr-4 py-2 text-xs font-bold italic text-white focus:ring-0 outline-none">
            </div>
            
            <select x-model="tipo" @change="currentPage = 1; loadProducts()" class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-xl px-4 py-2 focus:ring-indigo-500 outline-none">
                <option value="">📦 Todos os Tipos</option>
                <option value="simples">Simples</option>
                <option value="variacao">Variação</option>
                <option value="composto">Kit / Composto</option>
            </select>

            <select x-model="status" @change="currentPage = 1; loadProducts()" class="bg-slate-900 border-none text-white font-black italic text-xs uppercase rounded-xl px-4 py-2 focus:ring-indigo-500 outline-none">
                <option value="">Todos</option>
                <option value="1">✅ Ativos</option>
                <option value="0">❌ Inativos</option>
            </select>

            <!-- Menu 3 pontos -->
            <div class="relative" x-data="{ openMenu: false }">
                <button @click="openMenu = !openMenu" class="px-3 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-sm flex items-center gap-2">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div x-show="openMenu" @click.away="openMenu = false" class="absolute right-0 mt-2 w-48 bg-slate-800 border border-slate-700 rounded-xl shadow-xl z-50 overflow-hidden" style="display: none;">
                    <button @click="exportProducts(); openMenu = false" class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-slate-700 flex items-center gap-2">
                        <i class="fas fa-file-export text-emerald-400"></i>
                        Exportar Planilha
                    </button>
                    <label class="w-full px-4 py-3 text-left text-sm text-slate-300 hover:bg-slate-700 flex items-center gap-2 cursor-pointer">
                        <i class="fas fa-file-import text-amber-400"></i>
                        Importar Planilha
                        <input type="file" accept=".xlsx,.xls,.csv" @change="importProducts($event)" class="hidden">
                    </label>
                </div>
            </div>

            <a href="/products/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-black italic uppercase text-xs transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Novo Produto
            </a>
        </div>
    </div>

    <!-- Grid de Produtos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="product in products" :key="product.id">
            <div class="bg-slate-800 border border-slate-700 rounded-3xl overflow-hidden shadow-xl hover:border-indigo-500/30 transition-all group flex flex-col h-full">
                <!-- Imagem/Header -->
                <div class="aspect-video bg-slate-900 relative overflow-hidden">
                    <img x-show="product.foto_principal" :src="product.foto_principal" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    <div x-show="!product.foto_principal" class="w-full h-full flex items-center justify-center bg-slate-900">
                        <i class="fas fa-box text-slate-700 text-4xl group-hover:rotate-12 transition-transform"></i>
                    </div>
                    <div class="absolute top-3 left-3 flex gap-2">
                        <span class="px-2 py-0.5 rounded-lg bg-indigo-600/80 backdrop-blur-md text-[10px] font-black italic uppercase text-white shadow-lg" x-text="product.tipo"></span>
                        <span x-show="!product.ativo" class="px-2 py-0.5 rounded-lg bg-rose-600/80 backdrop-blur-md text-[10px] font-black italic uppercase text-white shadow-lg">Inativo</span>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-6 flex-1 flex flex-col">
                    <div class="mb-4">
                        <h3 class="font-black text-white italic uppercase tracking-tighter text-sm line-clamp-2 leading-tight group-hover:text-indigo-400 transition-colors" x-text="product.nome"></h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1 tracking-widest" x-text="product.sku || 'SEM SKU'"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="p-3 bg-slate-900/50 rounded-2xl border border-slate-700/50">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Preço Venda</p>
                            <p class="text-sm font-black text-indigo-400 italic" x-text="formatMoney(product.preco_venda)"></p>
                        </div>
                        <div class="p-3 bg-slate-900/50 rounded-2xl border border-slate-700/50 text-right">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Estoque</p>
                            <p class="text-sm font-black italic" :class="product.estoque > 0 ? 'text-emerald-400' : 'text-rose-500'" x-text="product.estoque || 0"></p>
                        </div>
                    </div>

                    <div class="mt-auto pt-4 border-t border-slate-700/50 flex items-center justify-between">
                        <div class="flex gap-2">
                            <a :href="'/products/edit?id=' + product.id" class="w-9 h-9 rounded-xl bg-slate-700 text-slate-300 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                            <button @click="deleteProduct(product.id)" class="w-9 h-9 rounded-xl bg-slate-700 text-slate-400 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                        <div class="flex items-center gap-1 opacity-50 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-tag text-[10px] text-slate-600"></i>
                            <span class="text-[10px] font-bold text-slate-600 uppercase italic" x-text="product.categoria?.nome || 'Geral'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="col-span-full p-20 text-center text-indigo-500">
        <i class="fas fa-circle-notch fa-spin text-4xl mb-4"></i>
        <p class="text-sm font-black uppercase tracking-widest animate-pulse">Sincronizando Catálogo...</p>
    </div>

    <!-- Empty -->
    <div x-show="!loading && products.length === 0" class="col-span-full p-20 text-center text-slate-600 italic">
        <i class="fas fa-box-open text-4xl mb-4 opacity-20"></i>
        <p class="text-sm font-black uppercase tracking-widest">Nenhum produto encontrado para esta empresa</p>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && total > 0" class="flex items-center justify-between mt-4 bg-slate-800 rounded-xl border border-slate-700 p-4">
        <div class="text-sm text-slate-400">
            Mostrando <span class="text-white font-bold" x-text="from"></span> - <span class="text-white font-bold" x-text="to"></span> de <span class="text-white font-bold" x-text="total"></span> produtos
        </div>
        <div class="flex items-center gap-2">
            <button @click="changePage(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="text-sm text-slate-400">Página <span class="text-white font-bold" x-text="currentPage"></span> de <span class="text-white font-bold" x-text="lastPage"></span></span>
            <button @click="changePage(currentPage + 1)" :disabled="currentPage >= lastPage" class="px-3 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function productsPage() {
    return {
        products: [],
        loading: false,
        search: '',
        tipo: '',
        status: '1',
        currentPage: 1,
        lastPage: 1,
        total: 0,
        from: 0,
        to: 0,
        
        async init() {
            // Get empresa from localStorage
            const savedEmpresa = localStorage.getItem('empresa_id');
            this.empresaId = savedEmpresa ? parseInt(savedEmpresa) : 6;
            
            await this.loadProducts();
            
            // Listen for empresa changes from the layout
            window.addEventListener('empresa-changed', (e) => {
                this.empresaId = parseInt(e.detail);
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadProducts();
            });
        },
        
        changePage(page) {
            if (page >= 1 && page <= this.lastPage) {
                this.currentPage = page;
                this.loadProducts();
            }
        },
        
        async loadProducts() {
            this.loading = true;
            try {
                // Pegar empresa do localStorage ou usar 6 como default (LideraMix)
                const empresaId = localStorage.getItem('empresa_id') || '6';
                const params = new URLSearchParams({
                    empresa: empresaId,
                    search: this.search,
                    tipo: this.tipo,
                    status: this.status,
                    page: this.currentPage
                });
                
                const response = await fetch(`/api/products?${params}`);
                if (response.ok) {
                    const result = await response.json();
                    this.products = result.data || result;
                    this.currentPage = result.current_page || 1;
                    this.lastPage = result.last_page || 1;
                    this.total = result.total || 0;
                    this.from = result.from || 0;
                    this.to = result.to || 0;
                }
            } catch (e) {
                console.error('Erro ao carregar produtos:', e);
            }
            this.loading = false;
        },

        async deleteProduct(id) {
            if(!confirm('Deseja realmente excluir este SKU?')) return;
            
            try {
                const response = await fetch(`/api/products/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (response.ok) {
                    await this.loadProducts();
                }
            } catch (e) {
                console.error('Erro ao excluir:', e);
            }
        },
        
        exportProducts() {
            const empresaId = localStorage.getItem('empresa_id') || '6';
            window.location.href = `/api/products/export?empresa=${empresaId}`;
        },
        
        async importProducts(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            
            const empresaId = localStorage.getItem('empresa_id') || '6';
            
            try {
                const response = await fetch(`/api/products/import?empresa=${empresaId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    this.loadProducts();
                } else {
                    alert(data.message || 'Erro na importação');
                }
            } catch (e) {
                console.error('Erro ao importar:', e);
                alert('Erro ao importar planilha');
            }
            
            event.target.value = '';
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        }
    }
}
</script>
@endsection
