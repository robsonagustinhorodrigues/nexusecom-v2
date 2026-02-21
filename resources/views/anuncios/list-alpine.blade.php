<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anúncios - NexusEcom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-white" x-data="anuncios()" x-init="init()">

    <!-- Top Bar -->
    <header class="fixed top-0 left-0 right-0 h-14 bg-slate-900/95 backdrop-blur border-b border-slate-800 z-50 flex items-center justify-between px-4">
        <div class="flex items-center gap-4">
            <a href="/dashboard-refined" class="p-2 hover:bg-slate-800 rounded-lg">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center">
                    <i class="fas fa-bullhorn text-white text-sm"></i>
                </div>
                <span class="font-bold text-lg">Anúncios</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Marketplace Filter -->
            <select x-model="marketplace" @change="loadAnuncios()" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm">
                <option value="">Todos</option>
                <option value="mercadolivre">Mercado Livre</option>
                <option value="amazon">Amazon</option>
                <option value="shopee">Shopee</option>
            </select>

            <!-- Status Filter -->
            <select x-model="statusFilter" @change="loadAnuncios()" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm">
                <option value="">Todos</option>
                <option value="active">Ativos</option>
                <option value="paused">Pausados</option>
                <option value="pending">Pending</option>
            </select>

            <button @click="syncAnuncios()" :disabled="syncing" class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 disabled:opacity-50">
                <i :class="syncing ? 'fa-spin' : ''" class="fas fa-sync-alt"></i>
                <span class="hidden sm:inline" x-text="syncing ? 'Sincronizando...' : 'Sincronizar'"></span>
            </button>
        </div>
    </header>

    <main class="lg:ml-64 pt-14 min-h-screen pb-8">
        <div class="p-6">
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 text-xs">Total</span>
                        <i class="fas fa-tags text-blue-400"></i>
                    </div>
                    <p class="text-2xl font-bold mt-1" x-text="stats.total">0</p>
                </div>
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 text-xs">Ativos</span>
                        <i class="fas fa-check-circle text-emerald-400"></i>
                    </div>
                    <p class="text-2xl font-bold mt-1 text-emerald-400" x-text="stats.active">0</p>
                </div>
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 text-xs">Catálogo</span>
                        <i class="fas fa-book text-amber-400"></i>
                    </div>
                    <p class="text-2xl font-bold mt-1 text-amber-400" x-text="stats.catalogo">0</p>
                </div>
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 text-xs">Vinculados</span>
                        <i class="fas fa-link text-indigo-400"></i>
                    </div>
                    <p class="text-2xl font-bold mt-1 text-indigo-400" x-text="stats.linked">0</p>
                </div>
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 text-xs">Vendidos</span>
                        <i class="fas fa-shopping-cart text-purple-400"></i>
                    </div>
                    <p class="text-2xl font-bold mt-1 text-purple-400" x-text="stats.sold">0</p>
                </div>
            </div>

            <!-- Loading -->
            <div x-show="loading" class="text-center py-12">
                <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
                <p class="text-slate-400 mt-2">Carregando anúncios...</p>
            </div>

            <!-- Ads Grid -->
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <template x-for="ad in anuncios" :key="ad.id">
                    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden hover:border-yellow-500/50 transition-all group">
                        <!-- Image -->
                        <div class="relative h-40 bg-gradient-to-br from-yellow-500/20 to-orange-600/20">
                            <img x-show="ad.thumbnail" :src="ad.thumbnail" class="w-full h-full object-cover">
                            <div x-show="!ad.thumbnail" class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-image text-4xl text-slate-600"></i>
                            </div>
                            
                            <!-- Badges -->
                            <div class="absolute top-2 left-2 flex gap-1">
                                <span class="text-xs px-2 py-1 rounded-lg font-bold bg-white/20 backdrop-blur-sm text-white flex items-center gap-1"
                                    :class="{
                                        'bg-yellow-500/20 text-yellow-400': ad.marketplace === 'mercadolivre',
                                        'bg-orange-500/20 text-orange-400': ad.marketplace === 'amazon',
                                        'bg-pink-500/20 text-pink-400': ad.marketplace === 'shopee'
                                    }"
                                >
                                    <i :class="getMarketplaceIcon(ad.marketplace)"></i>
                                    <span x-text="getMarketplaceLabel(ad.marketplace)"></span>
                                </span>
                                <span x-show="ad.has_promotion" class="text-xs px-2 py-1 rounded-lg font-bold bg-rose-500 text-white">
                                    <i class="fas fa-tag"></i> OFF
                                </span>
                            </div>

                            <!-- Status & Repricer -->
                            <div class="absolute top-2 right-2 flex flex-col gap-1 items-end">
                                <span class="text-xs px-2 py-1 rounded-lg font-bold"
                                    :class="{
                                        'bg-emerald-500 text-white': ad.status === 'active',
                                        'bg-slate-500 text-white': ad.status === 'paused',
                                        'bg-amber-500 text-white': ad.status === 'pending'
                                    }"
                                    x-text="getStatusLabel(ad.status)"
                                ></span>
                                <span x-show="ad.repricer_active" class="text-xs px-2 py-1 rounded-lg font-bold bg-indigo-600 text-white flex items-center gap-1">
                                    <i class="fas fa-robot"></i> ROBÔ
                                </span>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="p-4">
                            <!-- Title -->
                            <h3 class="font-semibold text-sm line-clamp-2 mb-2" x-text="ad.title"></h3>
                            
                            <!-- ID & SKU -->
                            <div class="flex items-center gap-2 mb-3 text-xs text-slate-400">
                                <span x-text="ad.external_id"></span>
                                <span x-show="ad.sku" class="font-mono bg-indigo-900 text-indigo-300 px-2 py-0.5 rounded" x-text="'ML: ' + ad.sku"></span>
                            </div>

                            <!-- Price & Profit -->
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <div>
                                    <p class="text-xs text-slate-500">Preço</p>
                                    <p class="text-lg font-bold" :class="ad.has_promotion ? 'text-rose-400' : 'text-emerald-400'" x-text="formatMoney(ad.lucro.preco)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Lucro</p>
                                    <p class="text-lg font-bold" :class="ad.lucro.margem > 0 ? 'text-emerald-400' : 'text-rose-400'">
                                        <span x-text="formatMoney(ad.lucro.lucro_bruto)"></span>
                                        <span class="text-xs" x-text="'(' + ad.lucro.margem.toFixed(1) + '%)'"></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Fees & Costs -->
                            <div class="text-xs text-slate-400 mb-3 bg-slate-900/50 rounded-lg p-2 space-y-1">
                                <div class="flex justify-between">
                                    <span>Taxa (<span x-text="ad.lucro.taxa_percent.toFixed(0)"></span>%):</span>
                                    <span class="text-rose-400">- <span x-text="formatMoney(ad.lucro.taxas)"></span></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="flex items-center gap-1">
                                        Frete:
                                        <span x-show="ad.lucro.frete_gratis" class="text-[10px] bg-emerald-500/20 text-emerald-400 px-1 rounded">FREE</span>
                                        <span x-show="!ad.lucro.frete_gratis && ad.lucro.frete_type === 'fulfillment'" class="text-[10px] bg-emerald-500/20 text-emerald-400 px-1 rounded">FULL</span>
                                    </span>
                                    <span class="text-rose-400">- <span x-text="formatMoney(ad.lucro.frete)"></span></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Imposto (10%):</span>
                                    <span class="text-rose-400">- <span x-text="formatMoney(ad.lucro.imposto)"></span></span>
                                </div>
                                <div x-show="ad.lucro.custo > 0 || ad.lucro.custo_adicional > 0" class="border-t border-slate-700 pt-2 mt-2">
                                    <div class="flex justify-between text-slate-400">
                                        <span>Custo base:</span>
                                        <span class="text-rose-400">- <span x-text="formatMoney(ad.lucro.custo)"></span></span>
                                    </div>
                                    <div x-show="ad.lucro.custo_adicional > 0" class="flex justify-between text-slate-400">
                                        <span><i class="fas fa-tag text-amber-500 mr-1"></i>Adicional:</span>
                                        <span class="text-amber-400">- <span x-text="formatMoney(ad.lucro.custo_adicional)"></span></span>
                                    </div>
                                    <div class="flex justify-between font-semibold text-slate-300">
                                        <span>Total custo:</span>
                                        <span class="text-rose-400">- <span x-text="formatMoney(ad.lucro.custo_total)"></span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Dimensions -->
                            <div x-show="Object.keys(ad.medidas || {}).length > 0" class="text-xs mb-3 bg-amber-900/20 border border-amber-800 rounded-lg p-2">
                                <div class="font-semibold text-amber-400 mb-1"><i class="fas fa-box mr-1"></i> Embalagem</div>
                                <div class="grid grid-cols-2 gap-1 text-slate-300">
                                    <span x-show="ad.medidas.altura">A: <span x-text="ad.medidas.altura"></span></span>
                                    <span x-show="ad.medidas.largura">L: <span x-show="ad.medidas.largura" x-text="ad.medidas.largura"></span></span>
                                    <span x-show="ad.medidas.comprimento">C: <span x-show="ad.medidas.comprimento" x-text="ad.medidas.comprimento"></span></span>
                                    <span x-show="ad.medidas.peso">⚖️ <span x-text="ad.medidas.peso"></span></span>
                                </div>
                            </div>

                            <!-- Product Link -->
                            <div class="text-xs mb-3 flex items-center gap-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                    :class="ad.product_linked ? 'bg-indigo-500/20 text-indigo-400' : 'bg-slate-700 text-slate-400'"
                                >
                                    <i :class="ad.product_linked ? 'fa-link' : 'fa-unlink'"></i>
                                    <span x-text="ad.product_linked ? 'Vinculado' : 'Não vinculado'"></span>
                                </span>
                                <span x-show="ad.product_linked && ad.product_stock !== null" class="text-slate-400">
                                    Estoque: <span class="text-blue-400 font-bold" x-text="ad.product_stock"></span>
                                </span>
                            </div>

                            <!-- Sold & Stock -->
                            <div class="flex items-center justify-between mb-3 text-xs">
                                <span class="text-slate-400">Vendidos: <span class="text-purple-400 font-bold" x-text="ad.sold_quantity || 0"></span></span>
                                <span class="text-slate-400">Estoque: <span class="text-blue-400 font-bold" x-text="ad.stock || 0"></span></span>
                            </div>

                            <!-- Actions Dropdown -->
                            <div class="relative" x-data="{ open: false }">
                                <button 
                                    @click="open = !open"
                                    class="w-full bg-slate-700 hover:bg-slate-600 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition"
                                >
                                    <i class="fas fa-cog"></i> Ações
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div 
                                    x-show="open"
                                    @click.away="open = false"
                                    x-transition
                                    class="absolute bottom-full left-0 right-0 mb-1 bg-slate-700 rounded-lg shadow-lg overflow-hidden z-10"
                                >
                                    <button @click="open = false; editAd(ad)" class="w-full text-left px-4 py-2.5 hover:bg-slate-600 text-sm flex items-center gap-2">
                                        <i class="fas fa-edit text-blue-400"></i> Editar
                                    </button>
                                    <button @click="open = false; toggleStatus(ad)" class="w-full text-left px-4 py-2.5 hover:bg-slate-600 text-sm flex items-center gap-2">
                                        <i :class="ad.status === 'active' ? 'fa-pause text-amber-400' : 'fa-play text-emerald-400'"></i>
                                        <span x-text="ad.status === 'active' ? 'Pausar' : 'Ativar'"></span>
                                    </button>
                                    <button @click="open = false; updatePrice(ad)" class="w-full text-left px-4 py-2.5 hover:bg-slate-600 text-sm flex items-center gap-2">
                                        <i class="fas fa-dollar-sign text-emerald-400"></i> Atualizar Preço
                                    </button>
                                    <button @click="open = false; updateStock(ad)" class="w-full text-left px-4 py-2.5 hover:bg-slate-600 text-sm flex items-center gap-2">
                                        <i class="fas fa-box text-blue-400"></i> Atualizar Estoque
                                    </button>
                                    <button @click="open = false; relinkProduct(ad)" class="w-full text-left px-4 py-2.5 hover:bg-slate-600 text-sm flex items-center gap-2">
                                        <i class="fas fa-link text-purple-400"></i> Vincular Produto
                                    </button>
                                    <button @click="open = false; viewDetails(ad)" class="w-full text-left px-4 py-2.5 hover:bg-slate-600 text-sm flex items-center gap-2">
                                        <i class="fas fa-eye text-slate-400"></i> Ver Detalhes
                                    </button>
                                    <hr class="border-slate-600">
                                    <button @click="open = false; deleteAd(ad)" class="w-full text-left px-4 py-2.5 hover:bg-red-600/20 text-red-400 text-sm flex items-center gap-2">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="!loading && anuncios.length === 0" class="text-center py-12">
                <i class="fas fa-tags text-4xl text-slate-600 mb-4"></i>
                <p class="text-slate-400">Nenhum anúncio encontrado</p>
                <button @click="syncAnuncios()" class="text-indigo-400 hover:text-indigo-300 text-sm mt-2 inline-block">
                    + Sincronizar anúncios
                </button>
            </div>

        </div>
    </main>

    <script>
    function anuncios() {
        return {
            loading: false,
            syncing: false,
            marketplace: '',
            statusFilter: '',
            anuncios: [],
            
            stats: {
                total: 0,
                active: 0,
                catalogo: 0,
                linked: 0,
                sold: 0
            },
            
            init() {
                this.loadAnuncios();
            },
            
            async loadAnuncios() {
                this.loading = true;
                
                const params = new URLSearchParams({
                    marketplace: this.marketplace,
                    status: this.statusFilter
                });
                
                try {
                    const response = await fetch(`/api/anuncios?${params}`);
                    const data = await response.json();
                    
                    this.anuncios = data.data || data || [];
                    this.calculateStats();
                } catch (e) {
                    console.error('Error:', e);
                    this.anuncios = [];
                }
                
                this.loading = false;
            },
            
            calculateStats() {
                this.stats.total = this.anuncios.length;
                this.stats.active = this.anuncios.filter(a => a.status === 'active').length;
                this.stats.catalogo = this.anuncios.filter(a => a.listing_type === 'catalogo').length;
                this.stats.linked = this.anuncios.filter(a => a.product_linked).length;
                this.stats.sold = this.anuncios.reduce((sum, a) => sum + (a.sold_quantity || 0), 0);
            },
            
            async syncAnuncios() {
                this.syncing = true;
                try {
                    const response = await fetch('/api/anuncios/sync', { method: 'POST' });
                    if (response.ok) {
                        await this.loadAnuncios();
                    }
                } catch (e) {
                    console.error('Sync error:', e);
                }
                this.syncing = false;
            },
            
            // Actions
            editAd(ad) {
                alert('Editar: ' + ad.title);
            },
            
            async toggleStatus(ad) {
                const newStatus = ad.status === 'active' ? 'paused' : 'active';
                try {
                    const response = await fetch(`/api/anuncios/${ad.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: newStatus })
                    });
                    if (response.ok) {
                        ad.status = newStatus;
                        this.calculateStats();
                    }
                } catch (e) {
                    console.error('Error:', e);
                }
            },
            
            updatePrice(ad) {
                const price = prompt('Novo preço:', ad.lucro.preco);
                if (price) {
                    this.updateAd(ad, { price: parseFloat(price) });
                }
            },
            
            updateStock(ad) {
                const stock = prompt('Novo estoque:', ad.stock);
                if (stock !== null) {
                    this.updateAd(ad, { stock: parseInt(stock) });
                }
            },
            
            async updateAd(ad, data) {
                try {
                    const response = await fetch(`/api/anuncios/${ad.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    if (response.ok) {
                        this.loadAnuncios();
                    }
                } catch (e) {
                    console.error('Error:', e);
                }
            },
            
            relinkProduct(ad) {
                const sku = prompt('Digite o SKU ou nome do produto para vincular:', ad.sku || '');
                if (sku) {
                    alert('Vincular: ' + sku + ' ao anúncio ' + ad.external_id);
                }
            },
            
            viewDetails(ad) {
                const details = `
Anúncio: ${ad.title}
ID: ${ad.external_id}
SKU: ${ad.sku || 'N/A'}
Status: ${ad.status}
Preço: R$ ${ad.lucro.preco.toFixed(2)}
Lucro: R$ ${ad.lucro.lucro_bruto.toFixed(2)} (${ad.lucro.margem.toFixed(1)}%)
Custo: R$ ${ad.lucro.custo.toFixed(2)}
Taxas: R$ ${ad.lucro.taxas.toFixed(2)}
Frete: R$ ${ad.lucro.frete.toFixed(2)}
Imposto: R$ ${ad.lucro.imposto.toFixed(2)}
Estoque: ${ad.stock}
Vendidos: ${ad.sold_quantity}
Vinculado: ${ad.product_linked ? 'Sim' : 'Não'}
                `;
                alert(details);
            },
            
            deleteAd(ad) {
                if (confirm('Tem certeza que deseja excluir este anúncio?')) {
                    alert('Excluir: ' + ad.title);
                }
            },
            
            // Helpers
            getMarketplaceIcon(mp) {
                const icons = {
                    'mercadolivre': 'fab fa-mercado-livre',
                    'amazon': 'fab fa-amazon',
                    'shopee': 'fab fa-shopify'
                };
                return icons[mp] || 'fas fa-store';
            },
            
            getMarketplaceLabel(mp) {
                const labels = {
                    'mercadolivre': 'ML',
                    'amazon': 'Amazon',
                    'shopee': 'Shopee'
                };
                return labels[mp] || mp;
            },
            
            getStatusLabel(status) {
                const labels = {
                    'active': 'Ativo',
                    'paused': 'Pausado',
                    'pending': 'Pendente'
                };
                return labels[status] || status;
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
