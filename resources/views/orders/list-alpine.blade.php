@extends('layouts.alpine')

@section('title', 'Pedidos - NexusEcom')
@section('header_title', 'Pedidos')

@section('content')
<div x-data="ordersPage()" x-init="init()">
    <!-- Header com Filtros -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Pedidos Marketplace</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Acompanhamento de vendas em tempo real</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-4 mb-4">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="flex-1 min-w-[180px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input 
                        type="text" 
                        x-model="search"
                        @input.debounce.300ms="loadOrders()"
                        placeholder="Buscar pedido, cliente, SKU..."
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                    >
                </div>
            </div>
            
            <!-- Data De -->
            <div class="relative">
                <i class="fas fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                <input 
                    type="date" 
                    x-model="dataDe"
                    @change="loadOrders()"
                    class="bg-slate-900 border border-slate-700 rounded-lg pl-8 pr-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                    title="Data de"
                >
            </div>
            
            <span class="text-slate-500">até</span>
            
            <!-- Data Ate -->
            <div class="relative">
                <i class="fas fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                <input 
                    type="date" 
                    x-model="dataAte"
                    @change="loadOrders()"
                    class="bg-slate-900 border border-slate-700 rounded-lg pl-8 pr-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                    title="Data até"
                >
            </div>
            
            <select x-model="status" @change="loadOrders()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Status</option>
                <option value="paid">Pago</option>
                <option value="pending">Pendente</option>
                <option value="shipped">Enviado</option>
                <option value="delivered">Entregue</option>
                <option value="canceled">Cancelado</option>
            </select>
            
            <select x-model="statusEnvio" @change="loadOrders()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Envio</option>
                <option value="pending">Aguardando</option>
                <option value="shipped">Enviado</option>
                <option value="delivered">Entregue</option>
                <option value="not_delivered">Não entregue</option>
            </select>
            
            <select x-model="logistics" @change="loadOrders()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Logística</option>
                <option value="me2">Mercado Envios</option>
                <option value="fulfillment">Fulfillment</option>
                <option value="classic">Classic</option>
            </select>
            
            <select x-model="marketplace" @change="loadOrders()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm">
                <option value="">Marketplace</option>
                <option value="mercadolivre">Mercado Livre</option>
                <option value="amazon">Amazon</option>
                <option value="bling">Bling</option>
            </select>
            
            <!-- Dropdown Sincronizar -->
            <div class="relative ml-auto">
                <button @click="syncDropdownOpen = !syncDropdownOpen" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg flex items-center gap-2 text-sm text-slate-300">
                    <i class="fas fa-sync"></i>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                
                <div x-show="syncDropdownOpen" @click.away="syncDropdownOpen = false" class="absolute right-0 mt-2 w-64 bg-slate-800 border border-slate-700 rounded-lg shadow-xl z-50 overflow-hidden">
                    <div x-show="!hasMeliIntegration" class="px-4 py-3 text-sm text-slate-400">
                        <i class="fas fa-info-circle mr-2"></i>
                        Nenhuma integração com Mercado Livre
                    </div>
                    <button x-show="hasMeliIntegration" @click="syncOrders('mercadolivre'); syncDropdownOpen = false" :disabled="syncing" 
                        class="w-full px-4 py-3 text-left text-sm text-slate-200 hover:bg-slate-700 flex items-center gap-3 disabled:opacity-50">
                        <i class="fab fa-mercadolivre text-yellow-400 text-lg"></i>
                        <span x-text="syncing ? 'Sincronizando...' : 'Sincronizar Mercado Livre'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats e Ações em Lote -->
    <div class="flex flex-wrap items-center gap-4 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 flex-1">
            <div class="bg-slate-800 rounded-lg border border-slate-700 p-3">
                <p class="text-xs text-slate-400">Pedidos</p>
                <p class="text-xl font-bold" x-text="orders.length"></p>
            </div>
            <div class="bg-slate-800 rounded-lg border border-slate-700 p-3">
                <p class="text-xs text-slate-400">Faturamento</p>
                <p class="text-xl font-bold text-emerald-400" x-text="formatMoney(totalValue)"></p>
            </div>
            <div class="bg-slate-800 rounded-lg border border-slate-700 p-3">
                <p class="text-xs text-slate-400">Lucro</p>
                <p class="text-xl font-bold" :class="totalProfit >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(totalProfit)"></p>
            </div>
            <div class="bg-slate-800 rounded-lg border border-slate-700 p-3">
                <p class="text-xs text-slate-400">Frete Médio</p>
                <p class="text-xl font-bold text-amber-400" x-text="formatMoney(avgFrete)"></p>
            </div>
        </div>
        
        <!-- Batch Actions -->
        <div x-show="selectedOrders.length > 0" class="flex items-center gap-2 bg-indigo-900/50 border border-indigo-700 rounded-lg px-3 py-2">
            <span class="text-sm text-indigo-300" x-text="selectedOrders.length + ' selecionado(s)'"></span>
            <button @click="printSelectedLabels()" class="text-xs px-2 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded flex items-center gap-1">
                <i class="fas fa-print"></i> Etiquetas
            </button>
            <button @click="selectedOrders = []" class="text-slate-400 hover:text-white p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
    </div>

    <!-- Orders List -->
    <div x-show="!loading" class="space-y-3">
        <!-- Header da Lista -->
        <div class="flex items-center gap-2 px-2 py-1 bg-slate-800/50 rounded text-xs text-slate-400">
            <input type="checkbox" 
                @change="toggleAll()" 
                :checked="selectedOrders.length > 0 && selectedOrders.length === orders.length"
                class="rounded bg-slate-700 border-slate-600"
            >
            <span class="flex-1">Pedido</span>
            <span class="w-20">Status</span>
            <span class="w-24">Logística</span>
            <span class="w-32">Valores</span>
            <span class="w-28">NFe</span>
            <span class="w-24">Ações</span>
        </div>
        
        <template x-for="order in orders" :key="order.id">
            <div class="bg-slate-800 rounded-lg border border-slate-700 p-3" :class="selectedOrders.includes(order.id) ? 'ring-2 ring-indigo-500' : ''">
                <!-- Linha Principal -->
                <div class="flex items-start gap-3">
                    <!-- Checkbox -->
                    <input type="checkbox" 
                        :checked="selectedOrders.includes(order.id)"
                        @change="toggleOrder(order.id)"
                        class="mt-2 rounded bg-slate-700 border-slate-600"
                    >
                    
                    <!-- Info Pedido -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded flex items-center justify-center flex-shrink-0"
                                    :class="getMarketplaceColor(order.marketplace)"
                                >
                                    <i :class="getMarketplaceIcon(order.marketplace)" class="text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-white text-sm" x-text="'#' + (order.pedido_id || order.id)"></p>
                                    <p class="text-xs text-slate-400" x-text="formatDate(order.data_compra)"></p>
                                </div>
                            </div>
                            
                            <!-- Status Badge -->
                            <span class="text-xs px-2 py-0.5 rounded-full whitespace-nowrap" :class="getStatusClass(order.status)" x-text="order.status"></span>
                        </div>
                        
                        <!-- Cliente & Localização -->
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                            <div class="flex items-center gap-1 text-slate-300">
                                <i class="fas fa-user text-slate-500"></i>
                                <span x-text="order.comprador_nome || 'Cliente não identificado'" class="max-w-[150px] truncate"></span>
                                <button @click="copyToClipboard(order.comprador_cpf || order.comprador_cnpj, 'CPF/CNPJ')" 
                                    x-show="order.comprador_cpf || order.comprador_cnpj"
                                    class="text-slate-500 hover:text-white" title="Copiar">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <div class="flex items-center gap-1 text-slate-400">
                                <i class="fas fa-map-marker-alt text-slate-500"></i>
                                <span x-text="order.cidade || '-'"></span>
                                <span x-text="order.estado || ''"></span>
                            </div>
                            <div class="flex items-center gap-1 text-slate-400">
                                <i class="fas fa-shipping-fast text-slate-500"></i>
                                <span x-text="order.status_envio || 'Aguardando'"></span>
                            </div>
                            <div x-show="order.codigo_rastreamento" class="flex items-center gap-1 text-slate-400">
                                <i class="fas fa-barcode text-slate-500"></i>
                                <span x-text="order.codigo_rastreamento"></span>
                                <button @click="copyToClipboard(order.codigo_rastreamento, 'Rastreio')" class="text-slate-500 hover:text-white" title="Copiar">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Itens (compacto) -->
                        <div x-show="order.itens && order.itens.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <template x-for="item in order.itens" :key="item.sku">
                                <div class="flex items-center gap-2 bg-slate-900/50 rounded px-2 py-1">
                                    <img x-show="item.thumbnail" :src="item.thumbnail" class="w-6 h-6 object-cover rounded" alt="">
                                    <div x-show="!item.thumbnail" class="w-6 h-6 bg-slate-700 rounded flex items-center justify-center">
                                        <i class="fas fa-image text-slate-500 text-xs"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <a :href="'https://produto.mercadolivre.com.br/MLB-' + item.item_id" target="_blank" 
                                            class="text-xs text-indigo-400 hover:text-indigo-300 truncate block max-w-[120px]" 
                                            x-text="item.sku || 'SEM SKU'"></a>
                                    </div>
                                    <span class="text-xs text-slate-500" x-text="'x' + item.quantidade"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Logística -->
                    <div class="w-20 text-center">
                        <span class="text-xs px-2 py-1 rounded-full" :class="getLogisticsClass(order.logistics?.mode)" 
                            x-text="getLogisticsLabel(order.logistics?.mode)"></span>
                    </div>
                    
                    <!-- Valores Consolidados -->
                    <div class="w-32 text-right text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Venda:</span>
                            <span class="text-white" x-text="formatMoney(order.valor_total)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Frete:</span>
                            <span class="text-white" x-text="formatMoney(order.valor_frete)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Taxa:</span>
                            <span class="text-red-400" x-text="'-' + formatMoney(order.taxas || 0)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Custo:</span>
                            <span class="text-amber-500" x-text="'-' + formatMoney(order.custo_total || 0)"></span>
                        </div>
                        <div class="flex justify-between font-bold border-t border-slate-700 pt-1 mt-1">
                            <span class="text-slate-400">Lucro:</span>
                            <span :class="(order.lucro || 0) >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatMoney(order.lucro || 0)"></span>
                        </div>
                    </div>
                    
                    <!-- NFe -->
                    <div class="w-24 text-center">
                        <template x-if="order.nfe_vinculada">
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-xs px-2 py-1 rounded-full bg-green-500/20 text-green-400" x-text="'#' + order.nfe_vinculada.numero"></span>
                                <div class="flex gap-1">
                                    <a :href="'/api/orders/' + order.id + '/danfe?empresa_id=' + empresaId" target="_blank" class="text-slate-400 hover:text-white p-1" title="Imprimir DANFE A4">
                                        <i class="fas fa-file-invoice text-xs"></i>
                                    </a>
                                    <a :href="'/api/orders/' + order.id + '/danfe-simplificada?empresa_id=' + empresaId" target="_blank" class="text-slate-400 hover:text-white p-1" title="Imprimir DANFE Simplificado">
                                        <i class="fas fa-receipt text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        </template>
                        <template x-if="!order.nfe_vinculada">
                            <span class="text-xs text-red-400">Sem NFe</span>
                        </template>
                    </div>
                    
                    <!-- Ações -->
                    <div class="w-24 flex flex-col items-end gap-1">
                        <!-- Dropdown Ações -->
                        <div class="relative">
                            <button @click="toggleActionsMenu(order.id)" class="text-slate-400 hover:text-white p-1">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            
                            <div x-show="actionsMenuOpen === order.id" @click.away="actionsMenuOpen = null" 
                                class="absolute right-0 mt-1 w-48 bg-slate-700 border border-slate-600 rounded-lg shadow-xl z-50 overflow-hidden text-xs">
                                
                                <!-- Etiqueta ML -->
                                <a :href="'/api/orders/' + order.id + '/etiqueta-meli?empresa_id=' + empresaId" target="_blank"
                                    class="block px-3 py-2 text-slate-200 hover:bg-slate-600 flex items-center gap-2">
                                    <i class="fas fa-tag w-4"></i> Etiqueta ML
                                </a>
                                
                                <!-- Etiqueta NFe (se houver NFe) -->
                                <a x-show="order.nfe_vinculada" :href="'/api/orders/' + order.id + '/etiqueta?empresa_id=' + empresaId" target="_blank"
                                    class="block px-3 py-2 text-slate-200 hover:bg-slate-600 flex items-center gap-2">
                                    <i class="fas fa-shipping-fast w-4"></i> Etiqueta NFe
                                </a>
                                
                                <!-- Ver no ML -->
                                <a :href="'https://www.mercadolivre.com.br/vendas/' + order.pedido_id + '/detalhe'" target="_blank"
                                    class="block px-3 py-2 text-slate-200 hover:bg-slate-600 flex items-center gap-2">
                                    <i class="fab fa-mercadolivre w-4"></i> Ver no ML
                                </a>
                                
                                <!-- Produto -->
                                <a x-show="order.item_id" :href="'https://produto.mercadolivre.com.br/MLB-' + order.item_id" target="_blank"
                                    class="block px-3 py-2 text-slate-200 hover:bg-slate-600 flex items-center gap-2">
                                    <i class="fas fa-external-link-alt w-4"></i> Ver Produto
                                </a>
                                
                                <div class="border-t border-slate-600"></div>
                                
                                <!-- Atualizar -->
                                <button @click="refreshOrder(order.id); actionsMenuOpen = null"
                                    class="w-full text-left px-3 py-2 text-slate-200 hover:bg-slate-600 flex items-center gap-2">
                                    <i class="fas fa-sync w-4"></i> Atualizar
                                </button>
                                
                                <!-- Ver JSON -->
                                <button @click="viewJson(order); actionsMenuOpen = null"
                                    class="w-full text-left px-3 py-2 text-slate-200 hover:bg-slate-600 flex items-center gap-2">
                                    <i class="fas fa-code w-4"></i> Ver JSON
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty -->
    <div x-show="!loading && orders.length === 0" class="text-center py-12">
        <i class="fas fa-shopping-cart text-4xl text-slate-600 mb-4"></i>
        <p class="text-slate-400">Nenhum pedido encontrado</p>
    </div>
    
    <!-- Pagination -->
    <div x-show="!loading && total > 0" class="flex items-center justify-between mt-4 px-2">
        <div class="text-sm text-slate-400">
            Mostrando <span x-text="from"></span> - <span x-text="to"></span> de <span x-text="total"></span>
        </div>
        <div class="flex items-center gap-2">
            <button @click="changePage(currentPage - 1)" :disabled="currentPage <= 1"
                class="px-3 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <template x-for="page in visiblePages" :key="page">
                <button @click="changePage(page)" 
                    class="px-3 py-1 rounded text-sm"
                    :class="page === currentPage ? 'bg-indigo-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                    x-text="page"></button>
            </template>
            
            <button @click="changePage(currentPage + 1)" :disabled="currentPage >= lastPage"
                class="px-3 py-1 rounded bg-slate-700 text-slate-300 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <!-- Modal JSON -->
    <div x-show="showJsonModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-100" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4" @click.self="closeJsonModal()">
        <div x-show="showJsonModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="bg-slate-800 rounded-xl border border-slate-600 w-full max-w-4xl max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between p-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white">JSON do Pedido</h3>
                <button @click="closeJsonModal()" class="text-slate-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="flex-1 overflow-auto p-4">
                <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap" x-text="JSON.stringify(currentJson, null, 2)"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function ordersPage() {
    return {
        empresaId: localStorage.getItem('empresa_id') || '4',
        orders: [],
        loading: false,
        syncing: false,
        search: '',
        status: '',
        statusEnvio: '',
        logistics: '',
        marketplace: '',
        dataDe: '',
        dataAte: '',
        hasMeliIntegration: false,
        syncDropdownOpen: false,
        showJsonModal: false,
        currentJson: null,
        selectedOrders: [],
        actionsMenuOpen: null,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        from: 0,
        to: 0,
        
        init() {
            const savedEmpresa = localStorage.getItem('empresa_id');
            this.empresaId = savedEmpresa ? parseInt(savedEmpresa) : 4;
            
            // Default date range: last 30 days
            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(today.getDate() - 30);
            this.dataAte = today.toISOString().split('T')[0];
            this.dataDe = thirtyDaysAgo.toISOString().split('T')[0];
            
            this.$watch('empresaId', () => {
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadOrders();
                this.loadIntegrations();
            });
            
            window.addEventListener('empresa-changed', (e) => {
                this.empresaId = parseInt(e.detail);
                localStorage.setItem('empresa_id', this.empresaId);
                this.loadOrders();
                this.loadIntegrations();
            });
            
            this.loadOrders();
            this.loadIntegrations();
        },
        
        async loadIntegrations() {
            try {
                const response = await fetch(`/api/orders/integrations?empresa_id=${this.empresaId}`);
                const data = await response.json();
                this.hasMeliIntegration = data.mercadolivre && data.mercadolivre.length > 0;
            } catch (e) {
                this.hasMeliIntegration = false;
            }
        },
        
        async loadOrders(resetPage = true) {
            if (resetPage) {
                this.currentPage = 1;
            }
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    empresa_id: this.empresaId,
                    status: this.status,
                    status_envio: this.statusEnvio,
                    logistics: this.logistics,
                    marketplace: this.marketplace,
                    search: this.search,
                    data_de: this.dataDe,
                    data_ate: this.dataAte,
                    page: this.currentPage,
                });
                
                const response = await fetch(`/api/orders?${params}`);
                const result = await response.json();
                this.orders = result.data || [];
                this.currentPage = result.current_page || 1;
                this.lastPage = result.last_page || 1;
                this.total = result.total || 0;
                this.from = result.from || 0;
                this.to = result.to || 0;
                this.selectedOrders = [];
            } catch (e) {
                console.error('Error:', e);
                this.orders = [];
            }
            this.loading = false;
        },
        
        changePage(page) {
            if (page >= 1 && page <= this.lastPage) {
                this.currentPage = page;
                this.loadOrders();
            }
        },
        
        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        
        async syncOrders(marketplace = 'mercadolivre') {
            this.syncing = true;
            try {
                const response = await fetch(`/api/orders/sync?empresa_id=${this.empresaId}&marketplace=${marketplace}`, { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Content-Type': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                } else {
                    alert('Erro: ' + data.message);
                }
                await this.loadOrders();
            } catch (e) {
                console.error('Sync error:', e);
            }
            this.syncing = false;
        },
        
        async refreshOrder(orderId) {
            try {
                const response = await fetch(`/api/orders/${orderId}/refresh?empresa_id=${this.empresaId}`, { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'Content-Type': 'application/json',
                    }
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    await this.loadOrders();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (e) {
                alert('Erro ao atualizar pedido');
            }
        },
        
        toggleOrder(orderId) {
            const idx = this.selectedOrders.indexOf(orderId);
            if (idx > -1) {
                this.selectedOrders.splice(idx, 1);
            } else {
                this.selectedOrders.push(orderId);
            }
        },
        
        toggleAll() {
            if (this.selectedOrders.length === this.orders.length) {
                this.selectedOrders = [];
            } else {
                this.selectedOrders = this.orders.map(o => o.id);
            }
        },
        
        printSelectedLabels() {
            this.selectedOrders.forEach(orderId => {
                const url = `/api/orders/${orderId}/etiqueta-meli?empresa_id=${this.empresaId}`;
                window.open(url, '_blank');
            });
        },
        
        toggleActionsMenu(orderId) {
            this.actionsMenuOpen = this.actionsMenuOpen === orderId ? null : orderId;
        },
        
        copyToClipboard(text, label) {
            if (!text) return;
            navigator.clipboard.writeText(text.toString()).then(() => {
                alert(label + ' copiado!');
            });
        },
        
        get totalValue() {
            return this.orders.reduce((sum, o) => sum + (o.valor_total || 0), 0);
        },
        
        get totalProfit() {
            return this.orders.reduce((sum, o) => sum + (o.lucro || 0), 0);
        },
        
        get avgFrete() {
            if (!this.orders.length) return 0;
            const total = this.orders.reduce((sum, o) => sum + (o.valor_frete || 0), 0);
            return total / this.orders.length;
        },
        
        get visiblePages() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.lastPage, start + maxVisible - 1);
            
            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },
        
        getMarketplaceIcon(mp) {
            const icons = { 'mercadolivre': 'fab fa-mercado-livre', 'amazon': 'fab fa-amazon', 'bling': 'fas fa-cash-register' };
            return icons[mp] || 'fas fa-store';
        },
        
        getMarketplaceColor(mp) {
            const colors = { 'mercadolivre': 'bg-yellow-500/20 text-yellow-400', 'amazon': 'bg-orange-500/20 text-orange-400', 'bling': 'bg-green-500/20 text-green-400' };
            return colors[mp] || 'bg-slate-600';
        },
        
        getStatusClass(status) {
            const classes = { 
                'paid': 'bg-green-500/20 text-green-400', 
                'pending': 'bg-yellow-500/20 text-yellow-400', 
                'shipped': 'bg-blue-500/20 text-blue-400',
                'delivered': 'bg-emerald-500/20 text-emerald-400',
                'canceled': 'bg-red-500/20 text-red-400',
                'em_aberto': 'bg-green-500/20 text-green-400',
                'enviado': 'bg-blue-500/20 text-blue-400',
                'entregue': 'bg-emerald-500/20 text-emerald-400',
            };
            return classes[status] || 'bg-slate-600 text-slate-300';
        },
        
        getLogisticsClass(mode) {
            const classes = { 
                'me2': 'bg-blue-500/20 text-blue-400',
                'fulfillment': 'bg-purple-500/20 text-purple-400',
                'classic': 'bg-orange-500/20 text-orange-400',
            };
            return classes[mode] || 'bg-slate-600 text-slate-400';
        },
        
        getLogisticsLabel(mode) {
            const labels = { 
                'me2': 'M.E2',
                'fulfillment': 'Full',
                'classic': 'Classic',
            };
            return labels[mode] || '-';
        },
        
        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('pt-BR');
        },
        
        formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
        },
        
        viewJson(order) {
            this.currentJson = order.json_data || {};
            this.showJsonModal = true;
        },
        
        closeJsonModal() {
            this.showJsonModal = false;
            this.currentJson = null;
        }
    }
}
</script>
@endsection
