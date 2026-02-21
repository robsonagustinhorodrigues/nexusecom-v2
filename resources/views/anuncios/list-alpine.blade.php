@extends('layouts.alpine')

@section('title', 'Anúncios - NexusEcom')
@section('header_title', 'Anúncios')

@section('content')
<div x-data="anunciosPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Meus Anúncios</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Gerencie anúncios dos marketplaces</p>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- View Mode -->
            <div class="flex bg-slate-800 rounded-xl p-1">
                <button @click="viewMode = 'cards'" :class="viewMode === 'cards' ? 'bg-slate-700 text-white' : 'text-slate-400'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all">
                    <i class="fas fa-th-large mr-1"></i> Cards
                </button>
                <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-slate-700 text-white' : 'text-slate-400'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all">
                    <i class="fas fa-list mr-1"></i> Tabela
                </button>
            </div>
            
            <button @click="syncAnuncios()" :disabled="syncing" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-400 text-slate-900 rounded-xl font-bold text-sm flex items-center gap-2">
                <i class="fas fa-sync" :class="syncing ? 'fa-spin' : ''"></i>
                <span x-text="syncing ? 'Sincronizando...' : 'Sincronizar'"></span>
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-4 mb-6">
        <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input type="text" x-model="search" @input.debounce.300ms="loadAnuncios()" placeholder="Buscar por título, ID ou SKU..." 
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-yellow-500 outline-none">
                </div>
            </div>
            <select x-model="marketplace" @change="loadAnuncios()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos marketplaces</option>
                <option value="mercadolivre">Mercado Livre</option>
                <option value="amazon">Amazon</option>
            </select>
            <select x-model="statusFilter" @change="loadAnuncios()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Status: Todos</option>
                <option value="active">Ativos</option>
                <option value="inactive">Inativos</option>
            </select>
            <select x-model="tipoFilter" @change="loadAnuncios()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Tipo: Todos</option>
                <option value="catalogo">Catálogo</option>
                <option value="normal">Normal</option>
            </select>
            <select x-model="vinculoFilter" @change="loadAnuncios()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Vínculo: Todos</option>
                <option value="vinculado">Vinculados</option>
                <option value="nao_vinculado">Não Vinculados</option>
            </select>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Total Anúncios</p>
            <p class="text-2xl font-bold" x-text="anuncios.length"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Ativos</p>
            <p class="text-2xl font-bold text-green-400" x-text="activeCount"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Inativos</p>
            <p class="text-2xl font-bold text-red-400" x-text="inactiveCount"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Sem Estoque</p>
            <p class="text-2xl font-bold text-amber-400" x-text="noStockCount"></p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-xs text-slate-400">Lucro Médio</p>
            <p class="text-2xl font-bold" :class="lucroMedio >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(lucroMedio)"></p>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-yellow-500"></i>
        <p class="text-slate-400 mt-2">Carregando anúncios...</p>
    </div>

    <!-- Cards View -->
    <div x-show="!loading && viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="anuncio in anuncios" :key="anuncio.id">
            <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden hover:border-yellow-500/50 transition-all">
                <div class="aspect-square bg-slate-700 relative">
                    <img x-show="anuncio.thumbnail" :src="anuncio.thumbnail" class="w-full h-full object-cover">
                    <div x-show="!anuncio.thumbnail" class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-3xl text-slate-600"></i>
                    </div>
                    <div class="absolute top-2 left-2">
                        <span class="text-xs px-2 py-1 rounded-full font-medium"
                            :class="anuncio.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'"
                            x-text="anuncio.status === 'active' ? 'Ativo' : 'Inativo'"
                        ></span>
                    </div>
                    <div class="absolute top-2 right-2">
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-800 text-slate-300" x-text="anuncio.marketplace"></span>
                    </div>
                    <div x-show="anuncio.is_catalog" class="absolute bottom-2 left-2">
                        <span class="text-[10px] px-2 py-1 rounded-full bg-purple-500/20 text-purple-400">Catálogo</span>
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-sm line-clamp-2 mb-2 text-white" x-text="anuncio.titulo"></h3>
                    
                    <div class="flex items-center justify-between mb-2 text-xs">
                        <span class="text-slate-400" x-text="'SKU: ' + (anuncio.sku || '-')"></span>
                        <span :class="(anuncio.estoque || 0) > 0 ? 'text-green-400' : 'text-red-400'" x-text="'Estoque: ' + (anuncio.estoque || 0)"></span>
                    </div>
                    
                    <!-- Lucratividade -->
                    <div class="bg-slate-700/50 rounded-lg p-2 mb-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-400">Preço</span>
                            <span class="text-white font-bold" x-text="formatMoney(anuncio.preco)"></span>
                        </div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-400">Custo</span>
                            <span class="text-red-400" x-text="formatMoney(anuncio.custo || 0)"></span>
                        </div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-slate-400">Taxas</span>
                            <span class="text-amber-400" x-text="formatMoney(anuncio.taxas || 0)"></span>
                        </div>
                        <div class="border-t border-slate-600 mt-1 pt-1 flex justify-between text-xs">
                            <span class="text-slate-400">Lucro</span>
                            <span class="font-bold" :class="(anuncio.lucro || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(anuncio.lucro || 0)"></span>
                        </div>
                        <div class="flex justify-between text-xs mt-1">
                            <span class="text-slate-400">Margem</span>
                            <span class="font-bold" :class="(anuncio.margem || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="(anuncio.margem || 0).toFixed(1) + '%'"></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold text-yellow-400" x-text="formatMoney(anuncio.preco)"></span>
                        <div class="flex gap-2">
                            <button @click="editAnuncio(anuncio)" class="p-2 bg-slate-700 hover:bg-indigo-600 rounded-lg text-xs text-white" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a :href="anuncio.url" target="_blank" class="p-2 bg-slate-700 hover:bg-yellow-500 hover:text-slate-900 rounded-lg text-xs" title="Ver no Marketplace">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Table View -->
    <div x-show="!loading && viewMode === 'table'" class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-400">Anúncio</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-400">SKU</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Preço</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Custo</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Taxas</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Lucro</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-400">Margem</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-400">Estoque</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-400">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-slate-400">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                <template x-for="anuncio in anuncios" :key="anuncio.id">
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img x-show="anuncio.thumbnail" :src="anuncio.thumbnail" class="w-10 h-10 rounded object-cover">
                                <div x-show="!anuncio.thumbnail" class="w-10 h-10 rounded bg-slate-700 flex items-center justify-center">
                                    <i class="fas fa-image text-slate-500 text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white line-clamp-1" x-text="anuncio.titulo"></p>
                                    <p class="text-xs text-slate-500" x-text="anuncio.marketplace"></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-400" x-text="anuncio.sku || '-'"></td>
                        <td class="px-4 py-3 text-right font-bold text-white" x-text="formatMoney(anuncio.preco)"></td>
                        <td class="px-4 py-3 text-right text-red-400" x-text="formatMoney(anuncio.custo || 0)"></td>
                        <td class="px-4 py-3 text-right text-amber-400" x-text="formatMoney(anuncio.taxas || 0)"></td>
                        <td class="px-4 py-3 text-right font-bold" :class="(anuncio.lucro || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(anuncio.lucro || 0)"></td>
                        <td class="px-4 py-3 text-right font-bold" :class="(anuncio.margem || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="(anuncio.margem || 0).toFixed(1) + '%'"></td>
                        <td class="px-4 py-3 text-center" :class="(anuncio.estoque || 0) > 0 ? 'text-green-400' : 'text-red-400'" x-text="anuncio.estoque || 0"></td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full" :class="anuncio.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'" x-text="anuncio.status === 'active' ? 'Ativo' : 'Inativo'"></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="editAnuncio(anuncio)" class="p-2 hover:bg-slate-700 rounded text-slate-400 hover:text-white">
                                <i class="fas fa-edit text-xs"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Empty -->
    <div x-show="!loading && anuncios.length === 0" class="text-center py-12">
        <i class="fas fa-store text-4xl text-slate-600 mb-4"></i>
        <p class="text-slate-400">Nenhum anúncio encontrado</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
function anunciosPage() {
    return {
        empresaId: localStorage.getItem('empresa_id') || '6',
        loading: false,
        syncing: false,
        viewMode: 'cards',
        marketplace: '',
        statusFilter: '',
        tipoFilter: '',
        vinculoFilter: '',
        search: '',
        anuncios: [],
        
        init() {
            this.$watch('empresaId', () => {
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadAnuncios();
            });
            this.loadAnuncios();
        },
        
        async loadAnuncios() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    empresa: this.empresaId,
                    marketplace: this.marketplace,
                    status: this.statusFilter,
                    tipo: this.tipoFilter,
                    vinculo: this.vinculoFilter,
                    search: this.search
                });
                
                const response = await fetch(`/api/anuncios?${params}`);
                const data = await response.json();
                this.anuncios = data.data || data;
            } catch (e) {
                console.error('Error:', e);
            }
            this.loading = false;
        },
        
        async syncAnuncios() {
            this.syncing = true;
            try {
                await fetch(`/api/anuncios/sync?empresa=${this.empresaId}`, { method: 'POST' });
                await this.loadAnuncios();
            } catch (e) {
                console.error('Sync error:', e);
            }
            this.syncing = false;
        },
        
        editAnuncio(anuncio) {
            window.location.href = `/anuncios/editar?id=${anuncio.id}`;
        },
        
        get activeCount() {
            return this.anuncios.filter(a => a.status === 'active').length;
        },
        
        get inactiveCount() {
            return this.anuncios.filter(a => a.status !== 'active').length;
        },
        
        get noStockCount() {
            return this.anuncios.filter(a => !a.estoque || a.estoque <= 0).length;
        },
        
        get lucroMedio() {
            if (this.anuncios.length === 0) return 0;
            const totalLucro = this.anuncios.reduce((sum, a) => sum + (a.lucro || 0), 0);
            return totalLucro / this.anuncios.length;
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        }
    }
}
</script>
@endsection
