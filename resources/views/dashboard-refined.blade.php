<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NexusEcom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        /* Smooth transitions */
        .transition-all { transition: all 0.2s ease; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        
        /* Status colors */
        .status-paid { background: #065f46; color: #34d399; }
        .status-pending { background: #78350f; color: #fbbf24; }
        .status-cancelled { background: #7f1d1d; color: #fca5a5; }
    </style>
</head>
<body class="bg-slate-900 text-white" x-data="dashboard()" x-init="init()">

    <!-- Top Bar -->
    <header class="fixed top-0 left-0 right-0 h-14 bg-slate-900/95 backdrop-blur border-b border-slate-800 z-50 flex items-center justify-between px-4">
        <!-- Left: Logo & Menu Toggle -->
        <div class="flex items-center gap-4">
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 hover:bg-slate-800 rounded-lg">
                <i class="fas fa-bars"></i>
            </button>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-bolt text-white text-sm"></i>
                </div>
                <span class="font-bold text-lg hidden sm:block">NEXUS</span>
            </div>
        </div>

        <!-- Center: Search -->
        <div class="flex-1 max-w-xl mx-4 hidden md:block">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                <input 
                    type="text" 
                    x-model="search"
                    @keyup.enter="performSearch()"
                    placeholder="Buscar produtos, pedidos, anúncios..."
                    class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                >
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center gap-3">
            <!-- Empresa Selector -->
            <select x-model="empresaId" @change="changeEmpresa()" class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none">
                <option value="4">MaxLider</option>
                <option value="5">LideraMais</option>
                <option value="6">LideraMix</option>
            </select>

            <!-- Notifications -->
            <button class="relative p-2 hover:bg-slate-800 rounded-lg">
                <i class="fas fa-bell text-slate-400"></i>
                <span x-show="notifications > 0" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- User -->
            <div class="flex items-center gap-2 pl-3 border-l border-slate-700">
                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-bold">
                    R
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside 
        class="fixed left-0 top-14 bottom-0 w-64 bg-slate-800 border-r border-slate-700 transform transition-transform z-40 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <nav class="p-3 space-y-1 overflow-y-auto h-full pb-20">
            <template x-for="item in menu" :key="item.url">
                <a 
                    :href="item.url" 
                    class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-slate-400 hover:bg-slate-700 hover:text-white transition-all"
                    :class="item.active ? 'bg-indigo-600/20 text-indigo-400' : ''"
                >
                    <i :class="item.icon + ' w-5'"></i>
                    <span class="font-medium text-sm" x-text="item.name"></span>
                    <span x-show="item.badge" class="ml-auto bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full" x-text="item.badge"></span>
                </a>
            </template>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 pt-14 min-h-screen">
        <div class="p-6">
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Vendas Hoje -->
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-xs uppercase font-semibold">Vendas Hoje</span>
                        <i class="fas fa-chart-line text-emerald-400"></i>
                    </div>
                    <p class="text-2xl font-bold text-white" x-text="formatMoney(stats.vendas_hoje)">R$ 0,00</p>
                    <p class="text-xs text-slate-500 mt-1"><span x-text="stats.pedidos_hoje">0</span> pedidos</p>
                </div>

                <!-- Pendentes -->
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-xs uppercase font-semibold">Pendentes</span>
                        <i class="fas fa-clock text-amber-400"></i>
                    </div>
                    <p class="text-2xl font-bold text-amber-400" x-text="stats.pedidos_pendentes">0</p>
                    <p class="text-xs text-slate-500 mt-1">Aguardando envio</p>
                </div>

                <!-- Anúncios Ativos -->
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-xs uppercase font-semibold">Anúncios</span>
                        <i class="fas fa-store text-blue-400"></i>
                    </div>
                    <p class="text-2xl font-bold text-blue-400" x-text="stats.anuncios_ativos">0</p>
                    <p class="text-xs text-slate-500 mt-1">Ativos no ML</p>
                </div>

                <!-- NF-es Hoje -->
                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-400 text-xs uppercase font-semibold">NF-e Hoje</span>
                        <i class="fas fa-file-invoice text-purple-400"></i>
                    </div>
                    <p class="text-2xl font-bold text-purple-400" x-text="stats.nfes_hoje">0</p>
                    <p class="text-xs text-slate-500 mt-1">Recebidas</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                <a href="/products/create-alpine" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-4 border border-slate-700 flex items-center gap-3 transition-all hover:border-emerald-500/50">
                    <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                        <i class="fas fa-plus text-emerald-400"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Novo Produto</p>
                        <p class="text-xs text-slate-500">Cadastrar</p>
                    </div>
                </a>

                <a href="/integrations/anuncios" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-4 border border-slate-700 flex items-center gap-3 transition-all hover:border-blue-500/50">
                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                        <i class="fas fa-tags text-blue-400"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Anúncios</p>
                        <p class="text-xs text-slate-500">Gerenciar</p>
                    </div>
                </a>

                <a href="/orders" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-4 border border-slate-700 flex items-center gap-3 transition-all hover:border-amber-500/50">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-amber-400"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Pedidos</p>
                        <p class="text-xs text-slate-500">Ver todos</p>
                    </div>
                </a>

                <a href="/fiscal/monitor" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-4 border border-slate-700 flex items-center gap-3 transition-all hover:border-purple-500/50">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                        <i class="fas fa-file-invoice-dollar text-purple-400"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Fiscal</p>
                        <p class="text-xs text-slate-500">NF-e</p>
                    </div>
                </a>
            </div>

            <!-- Orders Table -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
                <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                    <h3 class="font-bold">Últimos Pedidos</h3>
                    <a href="/orders" class="text-indigo-400 text-sm hover:text-indigo-300">Ver todos →</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Pedido</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Cliente</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Marketplace</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            <template x-for="order in orders" :key="order.id">
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-sm" x-text="order.external_id"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm" x-text="order.cliente_nome"></td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-1 rounded-full bg-yellow-500/20 text-yellow-400" x-text="order.marketplace"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-1 rounded-full" 
                                            :class="order.status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400'"
                                            x-text="order.status_text"
                                        ></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-emerald-400" x-text="formatMoney(order.valor_total)"></td>
                                </tr>
                            </template>
                            <tr x-show="orders.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                                    <i class="fas fa-inbox text-3xl mb-2 opacity-50"></i>
                                    <p>Nenhum pedido encontrado</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Sidebar Overlay (mobile) -->
    <div 
        x-show="sidebarOpen" 
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <script>
    function dashboard() {
        return {
            sidebarOpen: false,
            search: '',
            empresaId: '6',
            notifications: 2,
            loading: false,
            
            stats: {
                vendas_hoje: 0,
                pedidos_hoje: 0,
                pedidos_pendentes: 0,
                anuncios_ativos: 0,
                nfes_hoje: 0
            },
            
            orders: [],
            
            menu: [
                { name: 'Dashboard', url: '/dashboard-simple', icon: 'fas fa-home', active: true },
                { name: 'Produtos', url: '/products', icon: 'fas fa-box' },
                { name: 'Anúncios', url: '/integrations/anuncios', icon: 'fas fa-store', badge: '12' },
                { name: 'Pedidos', url: '/orders', icon: 'fas fa-shopping-bag', badge: '3' },
                { name: 'Estoque', url: '/estoque', icon: 'fas fa-warehouse' },
                { name: 'NF-e', url: '/fiscal/monitor', icon: 'fas fa-file-invoice' },
                { name: 'Financeiro', url: '/financial', icon: 'fas fa-chart-line' },
                { name: 'Integrações', url: '/integrations', icon: 'fas fa-plug' },
                { name: 'Configurações', url: '/admin/configuracoes', icon: 'fas fa-cog' },
            ],
            
            init() {
                this.loadData();
                
                // Auto-refresh every 60 seconds
                setInterval(() => {
                    this.loadData();
                }, 60000);
            },
            
            async loadData() {
                this.loading = true;
                
                try {
                    // Load stats
                    const statsRes = await fetch(`/api/dashboard/stats?empresa_id=${this.empresaId}`);
                    if (statsRes.ok) {
                        this.stats = await statsRes.json();
                    }
                    
                    // Load recent orders
                    const ordersRes = await fetch(`/api/orders/recent?empresa_id=${this.empresaId}&limit=5`);
                    if (ordersRes.ok) {
                        this.orders = await ordersRes.json();
                    }
                } catch (e) {
                    console.log('Using demo data');
                }
                
                this.loading = false;
            },
            
            changeEmpresa() {
                this.loadData();
            },
            
            performSearch() {
                if (this.search.trim()) {
                    window.location.href = `/products?search=${encodeURIComponent(this.search)}`;
                }
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
