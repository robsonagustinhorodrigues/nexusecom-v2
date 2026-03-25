@extends('layouts.alpine')

@section('title', 'Amazon Ads: Dashboard - NexusEcom')
@section('header_title', 'Amazon Ads - Dashboard de Automação')

@section('content')
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h2 class="font-bold text-2xl text-white flex items-center gap-3">
            <div class="p-2 bg-indigo-500/20 rounded-xl">
                <i class="fas fa-robot text-indigo-400"></i>
            </div>
            Dashboard de Automação
        </h2>
        <a href="{{ route('amazon-ads.settings') }}" class="bg-slate-700 hover:bg-slate-600 text-white rounded-xl px-4 py-2 font-bold flex items-center gap-2 transition-all shadow-lg active:scale-95">
            <i class="fas fa-cog"></i> Configurações LWA
        </a>
    </div>

    <div class="py-2" x-data="adsDashboard()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-slate-800/80 backdrop-blur-md rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden relative">
                
                <div x-show="loading" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center transition-all">
                    <div class="bg-slate-800 p-4 rounded-xl shadow-2xl flex items-center gap-3 border border-slate-700/50">
                        <i class="fas fa-circle-notch fa-spin text-indigo-500 text-xl"></i>
                        <span class="text-white font-medium">Carregando SKUs...</span>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-slate-700 bg-slate-900/30 px-6 overflow-x-auto custom-scrollbar">
                    <button @click="activeTab = 'skus'" 
                            :class="activeTab === 'skus' ? 'text-indigo-400 border-b-2 border-indigo-400 px-1' : 'text-slate-400 hover:text-slate-300'"
                            class="py-4 px-4 font-bold text-xs uppercase tracking-widest transition-all whitespace-nowrap">
                        <i class="fas fa-tasks mr-2"></i> Configuração de SKUs
                    </button>
                    <button @click="activeTab = 'monitoring'; loadCampaigns()" 
                            :class="activeTab === 'monitoring' ? 'text-indigo-400 border-b-2 border-indigo-400 px-1' : 'text-slate-400 hover:text-slate-300'"
                            class="py-4 px-4 font-bold text-xs uppercase tracking-widest transition-all whitespace-nowrap">
                        <i class="fas fa-chart-line mr-2"></i> Monitoramento de Campanhas
                    </button>
                </div>

                <div class="p-6">
                    <!-- SKU Config Tab -->
                    <div x-show="activeTab === 'skus'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                    <!-- Control Bar -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="relative w-full max-w-sm">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input type="text" x-model="search" placeholder="Buscar SKU..."
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2 text-white outline-none focus:border-indigo-500">
                        </div>
                        <button @click="openNewModal()" class="bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl px-4 py-2 font-bold shadow-lg transition-all active:scale-95">
                            <i class="fas fa-plus mr-2"></i> Adicionar SKU
                        </button>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left text-sm text-slate-300">
                            <thead class="bg-slate-900/50 text-slate-400 text-xs uppercase tracking-wider">
                                <tr>
                                    <th scope="col" class="px-6 py-4 rounded-tl-xl text-center w-16">Foto</th>
                                    <th scope="col" class="px-6 py-4">SKU / Anúncio</th>
                                    <th scope="col" class="px-6 py-4">Robô Ativo</th>
                                    <th scope="col" class="px-6 py-4">Margem Alvo</th>
                                    <th scope="col" class="px-6 py-4">Parametros Manuais</th>
                                    <th scope="col" class="px-6 py-4 text-right rounded-tr-xl">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50">
                                <template x-for="item in filteredSkus" :key="item.sku">
                                    <tr class="hover:bg-slate-700/20 transition-colors group">
                                        <td class="px-6 py-4">
                                            <img :src="item.anuncio?.thumbnail" class="w-10 h-10 rounded-lg object-cover bg-slate-900 border border-slate-700" onerror="this.src='/img/no-image.png'">
                                        </td>
                                        <td class="px-6 py-4 border-l-2 border-transparent group-hover:border-indigo-500">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-white text-base" x-text="item.sku"></span>
                                                <span class="text-[10px] text-slate-500 truncate max-w-[200px]" x-text="item.anuncio?.titulo || 'Sem anúncio vinculado'"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 text-xs font-bold rounded-full" 
                                                  :class="item.is_active ? 'bg-green-500/20 text-green-400' : 'bg-slate-700 text-slate-400'"
                                                  x-text="item.is_active ? 'ON' : 'OFF'"></span>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-indigo-300">
                                            <span x-text="item.margem_alvo ? item.margem_alvo + '%' : 'Default'"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-2">
                                                <span class="bg-slate-700 px-2 py-0.5 rounded text-xs" x-text="(item.keywords?.length || 0) + ' KWs'"></span>
                                                <span class="bg-slate-700 px-2 py-0.5 rounded text-xs" x-text="(item.categories?.length || 0) + ' CATs'"></span>
                                                <span class="bg-slate-700 px-2 py-0.5 rounded text-xs" x-text="(item.asins?.length || 0) + ' ASINs'"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="editItem(item)" class="text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 hover:bg-indigo-500/20 px-3 py-1 rounded-lg transition-colors font-bold text-xs shadow-sm border border-indigo-500/20">
                                                <i class="fas fa-edit mr-1"></i> Configurar
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="filteredSkus.length === 0" class="text-center py-10 text-slate-500">
                            Nenhum SKU configurado encontrado. Adicione um novo para começar.
                        </div>
                    </div>
                    </div>

                    <!-- Monitoring Tab -->
                    <div x-show="activeTab === 'monitoring'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-white font-bold flex items-center gap-2">
                                <i class="fas fa-layer-group text-slate-500"></i>
                                Campanhas Ativas na Amazon
                            </h4>
                            <button @click="syncCampaigns()" class="bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl px-4 py-2 text-sm font-bold transition-all shadow-lg active:scale-95 flex items-center gap-2" :disabled="syncing">
                                <i class="fas" :class="syncing ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                                <span x-text="syncing ? 'Sincronizando...' : 'Sincronizar Agora'"></span>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full whitespace-nowrap text-left text-sm text-slate-300">
                                <thead class="bg-slate-900/50 text-slate-400 text-xs uppercase tracking-wider">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 rounded-tl-xl font-black">Campanha</th>
                                        <th scope="col" class="px-6 py-4">Tipo</th>
                                        <th scope="col" class="px-6 py-4 text-center">Status</th>
                                        <th scope="col" class="px-6 py-4">Orçamento</th>
                                        <th scope="col" class="px-6 py-4 text-indigo-400">Gasto</th>
                                        <th scope="col" class="px-6 py-4 text-green-400">Vendas</th>
                                        <th scope="col" class="px-6 py-4 text-orange-400 rounded-tr-xl">ACOS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/50">
                                    <template x-for="camp in campaigns" :key="camp.id">
                                        <tr class="hover:bg-slate-700/20 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-white text-sm" x-text="camp.name"></span>
                                                    <span class="text-[10px] text-slate-500" x-text="'ID: ' + camp.campaign_id_amz"></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 uppercase text-[10px] font-bold text-slate-500" x-text="camp.type"></td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-0.5 text-[10px] font-black rounded-md uppercase" 
                                                      :class="camp.state === 'ENABLED' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'"
                                                      x-text="camp.state"></span>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-xs" x-text="'R$ ' + parseFloat(camp.daily_budget).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></td>
                                            <td class="px-6 py-4 font-mono text-xs text-indigo-300 font-bold" x-text="'R$ ' + (camp.metrics?.spend || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></td>
                                            <td class="px-6 py-4 font-mono text-xs text-green-300 font-bold" x-text="'R$ ' + (camp.metrics?.sales || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 bg-slate-900 rounded font-mono text-xs font-bold" 
                                                      :class="(camp.metrics?.acos || 0) > 25 ? 'text-red-400' : 'text-orange-400'"
                                                      x-text="(camp.metrics?.acos || 0) + '%'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div x-show="campaigns.length === 0" class="text-center py-10 text-slate-500">
                                <p class="mb-4">Nenhuma campanha sincronizada.</p>
                                <button @click="syncCampaigns()" class="bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-400 border border-indigo-500/30 rounded-xl px-6 py-2 font-bold transition-all active:scale-95 shadow-lg inline-flex items-center gap-2">
                                    <i class="fas fa-sync-alt"></i> Sincronizar Agora
                                </button>
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <!-- Setup Modal -->
        <div x-show="isModalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div @click.outside="isModalOpen = false" class="bg-slate-800 border border-slate-700 shadow-2xl rounded-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="bg-slate-900 px-6 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-robot text-indigo-400"></i>
                        <span>Configuração da Automação - SKU <span x-text="form.sku" class="text-indigo-400"></span></span>
                    </h3>
                    <button @click="isModalOpen = false" class="text-slate-400 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
                    
                    <!-- SKU Selection (Better than manual typing) -->
                    <div class="space-y-1 relative" x-data="{ open: false }">
                        <label class="block text-sm font-medium text-slate-300">Selecionar Anúncio da Amazon</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input type="text" 
                                x-model="listingSearch" 
                                @input.debounce.500ms="searchListings()"
                                @focus="open = true"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2 text-white focus:outline-none focus:border-indigo-500" 
                                placeholder="Buscar por título ou SKU do anúncio...">
                        </div>
                        
                        <!-- Search Results Dropdown -->
                        <div x-show="open && searchResults.length > 0" 
                             @click.outside="open = false"
                             class="absolute z-[110] w-full mt-1 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl max-h-60 overflow-y-auto custom-scrollbar">
                            <template x-for="res in searchResults" :key="res.id">
                                <div @click="selectListing(res); open = false" 
                                     class="flex items-center gap-3 p-3 hover:bg-slate-800 cursor-pointer transition-colors border-b border-slate-800 last:border-0">
                                    <img :src="res.thumbnail" class="w-10 h-10 rounded object-cover bg-slate-800" onerror="this.src='/img/no-image.png'">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-bold text-white truncate" x-text="res.titulo"></div>
                                        <div class="text-[10px] text-indigo-400 font-mono" x-text="'SKU: ' + res.sku"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="form.sku" class="mt-2 p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-indigo-400 uppercase font-black">SKU Selecionado</span>
                                <span class="text-white font-bold" x-text="form.sku"></span>
                            </div>
                            <i class="fas fa-check-circle text-indigo-500"></i>
                        </div>
                    </div>

                    <!-- Automaker Toggle -->
                    <div class="flex items-center justify-between bg-slate-900/50 p-4 rounded-xl border border-slate-700/50">
                        <div>
                            <h4 class="text-white font-bold">Robô Ativo (Automaker)</h4>
                            <p class="text-slate-400 text-xs mt-1">Ao ativar, o sistema criará as 5 campanhas e aplicará as regras A, B e C.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                            <div class="w-14 h-7 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-500"></div>
                        </label>
                    </div>

                    <!-- Margem Alvo -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-300">Margem Alvo Específica (%)</label>
                        <input type="number" step="0.01" x-model="form.margem_alvo" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-500" placeholder="Ex: Deixe em branco para usar a padrao (20%)">
                    </div>

                    <div class="border-t border-slate-700 my-4"></div>
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h4 class="text-white font-bold text-lg">Parâmetros Manuais (Opcional)</h4>
                            <p class="text-slate-400 text-xs text-balance">Esses dados serão usados para injetar nas Campanhas Manuais.</p>
                        </div>
                        <button @click="generateAiSuggestions()" 
                                :disabled="!form.anuncio_id || aiLoading"
                                class="flex items-center gap-2 px-3 py-1.5 bg-indigo-500/20 hover:bg-indigo-500/40 text-indigo-400 border border-indigo-500/30 rounded-lg text-xs font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas" :class="aiLoading ? 'fa-spinner fa-spin' : 'fa-robot'"></i>
                            <span x-text="aiLoading ? 'Gerando...' : 'Sugerir com IA'"></span>
                        </button>
                    </div>

                    <!-- Keywords -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-300">Palavras-chave (Até 10, separadas por vírgula)</label>
                        <input type="text" x-model="form.keywords_raw" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-500" placeholder="ex: fone bluetooth, fone sem fio">
                    </div>
                    
                    <!-- Categories -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-300">IDs de Categorias (Até 5, separados por vírgula)</label>
                        <input type="text" x-model="form.categories_raw" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-500" placeholder="ex: 12345, 67890">
                    </div>

                    <!-- ASINs -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-300">ASINs Concorrentes (Até 10, separados por vírgula)</label>
                        <input type="text" x-model="form.asins_raw" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-500" placeholder="ex: B08N5WRWNW, B08N5WRWNZ">
                    </div>

                </div>
                
                <div class="bg-slate-900 p-4 border-t border-slate-700 flex justify-end gap-3">
                    <button @click="isModalOpen = false" class="px-4 py-2 text-slate-300 hover:text-white font-medium transition-colors">Cancelar</button>
                    <button @click="saveItem()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-xl font-bold transition-all active:scale-95 shadow-lg flex items-center gap-2" :disabled="saving">
                        <i class="fas " :class="{'fa-save': !saving, 'fa-spinner fa-spin': saving}"></i>
                        <span x-text="saving ? 'Salvando...' : 'Salvar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adsDashboard', () => ({
                empresaId: {{ auth()->user()->current_empresa_id ?? 0 }},
                activeTab: 'skus',
                loading: false,
                saving: false,
                syncing: false,
                skus: [],
                campaigns: [],
                search: '',
                isModalOpen: false,
                isNew: false,
                
                form: {
                    anuncio_id: null,
                    sku: '',
                    is_active: false,
                    margem_alvo: '',
                    keywords_raw: '',
                    categories_raw: '',
                    asins_raw: ''
                },

                listingSearch: '',
                searchResults: [],
                aiLoading: false,

                init() {
                    this.loadData();
                },

                get filteredSkus() {
                    if (this.search === '') {
                        return this.skus;
                    }
                    return this.skus.filter(item => {
                        return item.sku.toLowerCase().includes(this.search.toLowerCase());
                    });
                },

                loadData() {
                    this.loading = true;
                    fetch(`/api/amazon-ads/sku-configs?empresa_id=${this.empresaId}`)
                        .then(r => r.json())
                        .then(data => {
                            this.skus = data;
                        })
                        .catch(err => console.error(err))
                        .finally(() => {
                            this.loading = false;
                        });
                },

                loadCampaigns() {
                    if (this.campaigns.length > 0 && !this.syncing) return;
                    this.loading = true;
                    fetch(`/api/amazon-ads/campaigns?empresa_id=${this.empresaId}`)
                        .then(r => r.json())
                        .then(data => {
                            this.campaigns = data;
                        })
                        .catch(err => console.error(err))
                        .finally(() => {
                            this.loading = false;
                        });
                },

                syncCampaigns() {
                    this.syncing = true;
                    fetch(`/api/amazon-ads/campaigns/sync`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ empresa_id: this.empresaId })
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.error) throw new Error(data.error);
                            this.loadCampaigns();
                        })
                        .catch(err => alert('Erro na sincronização: ' + err.message))
                        .finally(() => {
                            this.syncing = false;
                        });
                },

                openNewModal() {
                    this.isNew = true;
                    this.listingSearch = '';
                    this.searchResults = [];
                    this.form = {
                        anuncio_id: null,
                        sku: '',
                        is_active: false,
                        margem_alvo: '',
                        keywords_raw: '',
                        categories_raw: '',
                        asins_raw: ''
                    };
                    this.isModalOpen = true;
                },

                editItem(item) {
                    this.isNew = false;
                    this.listingSearch = item.anuncio?.titulo || '';
                    this.searchResults = [];
                    this.form = {
                        anuncio_id: item.marketplace_anuncio_id || null,
                        sku: item.sku,
                        is_active: item.is_active,
                        margem_alvo: item.margem_alvo || '',
                        keywords_raw: (item.keywords || []).join(', '),
                        categories_raw: (item.categories || []).join(', '),
                        asins_raw: (item.asins || []).join(', ')
                    };
                    this.isModalOpen = true;
                },

                searchListings() {
                    if (this.listingSearch.length < 3) {
                        this.searchResults = [];
                        return;
                    }
                    fetch(`/api/amazon-ads/listings/search?empresa_id=${this.empresaId}&q=${this.listingSearch}`)
                        .then(r => r.json())
                        .then(data => {
                            this.searchResults = data;
                        });
                },

                selectListing(listing) {
                    this.form.sku = listing.sku;
                    this.form.anuncio_id = listing.id;
                    this.listingSearch = listing.titulo;
                },

                generateAiSuggestions() {
                    if (!this.form.anuncio_id) return;
                    
                    this.aiLoading = true;
                    fetch(`/api/amazon-ads/ai/suggestions`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ anuncio_id: this.form.anuncio_id })
                    })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success && res.data) {
                                const suggestions = res.data;
                                if (suggestions.keywords) this.form.keywords_raw = suggestions.keywords.join(', ');
                                if (suggestions.categories) this.form.categories_raw = suggestions.categories.join(', ');
                                if (suggestions.asins) this.form.asins_raw = suggestions.asins.join(', ');
                            } else {
                                alert(res.message || 'Erro ao gerar sugestões');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Erro ao se comunicar com a IA');
                        })
                        .finally(() => {
                            this.aiLoading = false;
                        });
                },

                saveItem() {
                    if(!this.form.sku) {
                        alert('Digite o SKU!');
                        return;
                    }

                    this.saving = true;
                    
                    const payload = {
                        empresa_id: this.empresaId,
                        marketplace_anuncio_id: this.form.anuncio_id,
                        sku: this.form.sku,
                        is_active: this.form.is_active,
                        margem_alvo: this.form.margem_alvo ? parseFloat(this.form.margem_alvo) : null,
                        keywords: this.form.keywords_raw ? this.form.keywords_raw.split(',').map(s => s.trim()).filter(s => s) : [],
                        categories: this.form.categories_raw ? this.form.categories_raw.split(',').map(s => s.trim()).filter(s => s) : [],
                        asins: this.form.asins_raw ? this.form.asins_raw.split(',').map(s => s.trim()).filter(s => s) : [],
                    };

                    fetch(`/api/amazon-ads/sku-configs`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(r => {
                            if (!r.ok) throw new Error('Erro ao salvar');
                            return r.json();
                        })
                        .then(data => {
                            alert('Salvo com sucesso!');
                            this.isModalOpen = false;
                            this.loadData();
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Erro ao salvar');
                        })
                        .finally(() => {
                            this.saving = false;
                        });
                }
            }));
        });
    </script>
    @endsection
