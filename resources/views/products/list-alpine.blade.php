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

    <!-- Tabela Hierárquica de Produtos (Pai e Filhos) -->
    <div class="bg-slate-800 border-x border-t border-slate-700 rounded-t-3xl overflow-hidden shadow-2xl mt-4">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-900/80 border-b border-slate-700/50 text-slate-400 text-[10px] uppercase tracking-wider font-black">
                    <th class="py-4 px-4 w-12 text-center">+/-</th>
                    <th class="py-4 px-4 w-16">Foto</th>
                    <th class="py-4 px-4">Produto & Categoria</th>
                    <th class="py-4 px-4 w-40">SKU Base</th>
                    <th class="py-4 px-4 w-32">Tipo</th>
                    <th class="py-4 px-4 w-28 text-right">Custo Base</th>
                    <th class="py-4 px-4 w-32 text-right">Preço Venda</th>
                    <th class="py-4 px-4 w-28 text-center">Status</th>
                    <th class="py-4 px-4 w-24 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
                <template x-for="row in flatRows" :key="row.rowKey">

                    <!-- ROW: Parent OR Child -->
                    <tr x-show="row.isParent || expandedRows.includes(row.parentId)"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="transition-colors cursor-pointer"
                        :class="{
                            'hover:bg-slate-700/20': row.isParent && row.tipo === 'simples',
                            'bg-indigo-900/10 hover:bg-indigo-900/30 border-l-[3px] border-indigo-500': row.isParent && row.tipo === 'variacao',
                            'bg-amber-900/10 hover:bg-amber-900/30 border-l-[3px] border-amber-500': row.isParent && row.tipo === 'composto',
                            'bg-slate-900/40 hover:bg-slate-800/80 border-l-[4px] border-l-indigo-500/50': !row.isParent && row.parentTipo === 'variacao',
                            'bg-slate-900/40 hover:bg-slate-800/80 border-l-[4px] border-l-amber-500/50': !row.isParent && row.parentTipo === 'composto'
                        }"
                        @click="row.isParent && row.tipo !== 'simples' && getChildrenCount(row._product) > 0 ? toggleExpand(row.id, row.tipo) : null">
                        
                        <!-- Col 1: Toggle / Tree connector -->
                        <td class="py-3 px-4 text-center">
                            <div x-show="row.isParent">
                                <button x-show="row.tipo !== 'simples' && row.childCount > 0" 
                                        class="w-8 h-8 rounded-lg bg-slate-900/50 text-slate-400 hover:text-white hover:bg-indigo-600/50 flex items-center justify-center transition-all border border-slate-700"
                                        @click.stop="toggleExpand(row.id, row.tipo)">
                                    <i class="fas transition-transform duration-300" 
                                       :class="expandedRows.includes(row.id) ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                                </button>
                                <div x-show="row.tipo === 'simples' || row.childCount === 0" class="w-8 h-8 flex items-center justify-center opacity-30 mx-auto">
                                    <i class="fas fa-minus text-slate-600"></i>
                                </div>
                            </div>
                            <div x-show="!row.isParent" class="w-full flex justify-end">
                                <div class="w-6 h-6 border-l-2 border-b-2 border-slate-600 rounded-bl-lg opacity-50 relative -top-3"></div>
                            </div>
                        </td>
                        
                        <!-- Col 2: Foto / Icon -->
                        <td class="py-3 px-4">
                            <div x-show="row.isParent">
                                <div class="w-12 h-12 bg-slate-900 rounded-lg overflow-hidden border border-slate-700 flex items-center justify-center shadow-inner">
                                    <img x-show="row.foto" :src="row.foto" class="w-full h-full object-cover">
                                    <i x-show="!row.foto" class="fas fa-box text-slate-600 text-lg"></i>
                                </div>
                            </div>
                            <div x-show="!row.isParent">
                                <div class="w-10 h-10 bg-black/40 rounded-md overflow-hidden border border-slate-700/80 flex items-center justify-center">
                                    <i class="fas text-xs" :class="row.parentTipo === 'composto' ? 'fa-boxes text-amber-500/50' : 'fa-layer-group text-indigo-500/50'"></i>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Col 3: Nome / Titulo -->
                        <td class="py-3 px-4">
                            <template x-if="row.isParent">
                                <div class="flex flex-col">
                                    <h3 class="text-sm font-bold text-white uppercase italic truncate pr-4" x-text="row.nome" :title="row.nome"></h3>
                                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5"><i class="fas fa-tag mr-1 opacity-50"></i><span x-text="row.categoria"></span></span>
                                </div>
                            </template>
                            <template x-if="!row.isParent">
                                <h4 class="text-xs font-bold text-slate-300 italic uppercase" x-text="row.titulo"></h4>
                            </template>
                        </td>
                        
                        <!-- Col 4: SKU -->
                        <td class="py-3 px-4">
                            <span class="font-mono tracking-wider rounded border"
                                  :class="row.isParent ? 'text-xs text-slate-300 bg-slate-900 px-2.5 py-1 rounded-md border-slate-700/50' : 'text-[11px] text-slate-400 bg-black/40 px-2 py-0.5 border-slate-700'"
                                  x-text="row.sku || 'SEM SKU'"></span>
                        </td>
                        
                        <!-- Col 5: Tipo / Label -->
                        <td class="py-3 px-4">
                            <template x-if="row.isParent">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-black italic uppercase shadow-sm border"
                                      :class="{
                                          'bg-emerald-500/10 text-emerald-400 border-emerald-500/20': row.tipo === 'simples',
                                          'bg-indigo-500/10 text-indigo-400 border-indigo-500/20': row.tipo === 'variacao',
                                          'bg-amber-500/10 text-amber-400 border-amber-500/20': row.tipo === 'composto'
                                      }" x-text="row.tipo"></span>
                            </template>
                            <template x-if="!row.isParent">
                                <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest" x-text="row.label"></span>
                            </template>
                        </td>
                        
                        <!-- Col 6: Custo -->
                        <td class="py-3 px-4 text-right">
                            <span :class="row.isParent ? 'text-xs font-bold text-rose-400' : 'text-[10px] font-bold text-rose-400'" x-text="formatMoney(row.preco_custo)"></span>
                        </td>

                        <!-- Col 7: Preço Venda -->
                        <td class="py-3 px-4 text-right">
                            <span :class="row.isParent ? 'text-sm font-black text-emerald-400 italic' : 'text-xs font-black text-emerald-400 italic'" x-text="formatMoney(row.preco_venda)"></span>
                        </td>
                        
                        <!-- Col 8: Status -->
                        <td class="py-3 px-4 text-center">
                            <div class="inline-flex items-center justify-center" :class="!row.isParent ? 'opacity-70' : ''">
                                <span x-show="row.ativo" class="rounded-full bg-emerald-500" :class="row.isParent ? 'w-2.5 h-2.5 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'w-2 h-2'"></span>
                                <span x-show="!row.ativo" class="rounded-full bg-rose-500" :class="row.isParent ? 'w-2.5 h-2.5 shadow-[0_0_10px_rgba(244,63,94,0.5)]' : 'w-2 h-2'"></span>
                            </div>
                        </td>
                        
                        <!-- Col 9: Ações -->
                        <td class="py-3 px-4 text-right align-middle">
                            <template x-if="row.isParent">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="'/products/edit?id=' + row.id" @click.stop class="w-8 h-8 rounded-lg bg-slate-700/50 text-slate-300 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <button @click.stop="deleteProduct(row.id)" class="w-8 h-8 rounded-lg bg-slate-700/50 text-slate-400 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </template>
                            <template x-if="!row.isParent">
                                <a :href="'/products/edit?id=' + row.edit_id" @click.stop class="w-6 h-6 rounded bg-slate-800 text-slate-500 flex items-center justify-center hover:bg-slate-700 hover:text-white transition-all ml-auto" title="Visualizar">
                                    <i class="fas fa-eye text-[10px]"></i>
                                </a>
                            </template>
                        </td>
                    </tr>

                </template>
            </tbody>
        </table>
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
        expandedRows: [],
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
                this.expandedRows = []; // Close all rows when changing pages
                this.loadProducts();
            }
        },
        
        getChildrenCount(product) {
            if (product.tipo === 'variacao') return product.variations?.length || 0;
            if (product.tipo === 'composto') return product.components?.length || 0;
            return product.skus?.length || 0;
        },
        
        getChildren(product) {
            if (product.tipo === 'variacao' && product.variations) {
                return product.variations.map(v => {
                    const firstSku = v.skus?.[0] || {};
                    return {
                        uniqueId: 'var-' + v.id,
                        edit_id: v.id,
                        titulo: v.nome || 'Variação',
                        sku: firstSku.sku || v.sku || '',
                        label: ((v.variation_size || '') + ' ' + (v.variation_color || '')).trim() || 'Variação',
                        preco_custo: firstSku.preco_custo || v.preco_custo || product.preco_custo,
                        preco_venda: firstSku.preco_venda || v.preco_venda || product.preco_venda,
                        ativo: v.ativo
                    };
                });
            }
            if (product.tipo === 'composto' && product.components) {
                return product.components.map(c => {
                    const childData = c.component_product || {};
                    return {
                        uniqueId: 'comp-' + c.id,
                        edit_id: childData.id,
                        titulo: childData.nome || 'Componente',
                        sku: childData.sku || '',
                        label: c.quantity + 'x Unidades',
                        preco_custo: childData.preco_custo || 0,
                        preco_venda: c.unit_price || childData.preco_venda || 0,
                        ativo: childData.ativo !== false
                    };
                });
            }
            if (product.skus) {
                return product.skus.map(s => ({
                    uniqueId: 'sku-' + s.id,
                    edit_id: product.id,
                    titulo: s.descricao_sku || 'SKU Alternativo',
                    sku: s.sku,
                    label: s.label || 'Adicional',
                    preco_custo: s.preco_custo || product.preco_custo,
                    preco_venda: s.preco_venda || product.preco_venda,
                    ativo: s.ativo !== false
                }));
            }
            return [];
        },

        get flatRows() {
            let rows = [];
            for (const product of this.products) {
                // Parent row
                rows.push({
                    rowKey: 'parent-' + product.id,
                    isParent: true,
                    id: product.id,
                    _product: product,
                    nome: product.nome,
                    foto: product.foto_principal,
                    categoria: product.categoria?.nome || 'Sem Categoria',
                    sku: product.sku,
                    tipo: product.tipo,
                    preco_custo: product.preco_custo,
                    preco_venda: product.preco_venda,
                    ativo: product.ativo,
                    childCount: this.getChildrenCount(product)
                });
                // Child rows (immediately after parent)
                if (product.tipo !== 'simples') {
                    const children = this.getChildren(product);
                    for (const child of children) {
                        rows.push({
                            ...child,
                            rowKey: child.uniqueId,
                            isParent: false,
                            parentId: product.id,
                            parentTipo: product.tipo
                        });
                    }
                }
            }
            return rows;
        },

        toggleExpand(id, tipo) {
            if(tipo === 'simples') return;
            const index = this.expandedRows.indexOf(id);
            if (index > -1) {
                this.expandedRows.splice(index, 1);
            } else {
                this.expandedRows.push(id);
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
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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
