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
        .sidebar-item:hover { background: rgba(255,255,255,0.05); }
        .sidebar-item.active { background: rgba(99, 102, 241, 0.2); border-right: 3px solid #6366f1; }
    </style>
</head>
<body class="bg-slate-900 text-white" x-data="app()">

    <div class="flex min-h-screen">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 border-r border-slate-700 flex flex-col">
            <!-- Logo -->
            <div class="p-4 border-b border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <i class="fas fa-bolt text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg">NEXUS</h1>
                        <p class="text-xs text-slate-400">E-com Manager</p>
                    </div>
                </div>
            </div>

            <!-- Empresa Selector -->
            <div class="p-4 border-b border-slate-700">
                <select x-model="empresaId" @change="changeEmpresa()" class="w-full bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-sm">
                    <option value="4">MaxLider</option>
                    <option value="5">LideraMais</option>
                    <option value="6">LideraMix</option>
                </select>
            </div>

            <!-- Menu -->
            <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
                <template x-for="item in menu" :key="item.url">
                    <a 
                        :href="item.url" 
                        class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 transition"
                        :class="item.active ? 'active bg-indigo-500/20 text-indigo-400' : ''"
                    >
                        <i :class="item.icon + ' w-5'"></i>
                        <span class="font-medium" x-text="item.name"></span>
                        <span x-show="item.badge" class="ml-auto bg-indigo-500 text-xs px-2 py-0.5 rounded-full" x-text="item.badge"></span>
                    </a>
                </template>
            </nav>

            <!-- User -->
            <div class="p-4 border-t border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center">
                        <i class="fas fa-user text-slate-400"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm" x-text="userName"></p>
                        <p class="text-xs text-slate-400">Sair</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Header -->
            <header class="h-16 bg-slate-800 border-b border-slate-700 flex items-center justify-between px-6">
                <h2 class="text-xl font-bold" x-text="pageTitle"></h2>
                
                <div class="flex items-center gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input 
                            type="text" 
                            placeholder="Buscar..."
                            class="bg-slate-900 border border-slate-600 rounded-lg pl-10 pr-4 py-2 text-sm w-64 focus:border-indigo-500 focus:outline-none"
                        >
                    </div>

                    <!-- Notifications -->
                    <button class="relative p-2 text-slate-400 hover:text-white">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Vendas Hoje -->
                    <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-slate-400 text-sm">Vendas Hoje</span>
                            <i class="fas fa-chart-line text-indigo-400"></i>
                        </div>
                        <p class="text-2xl font-bold text-emerald-400">R$ 0,00</p>
                        <p class="text-xs text-slate-500 mt-1">0 pedidos</p>
                    </div>

                    <!-- Pedidos Pendentes -->
                    <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-slate-400 text-sm">Pedidos Pendentes</span>
                            <i class="fas fa-clock text-amber-400"></i>
                        </div>
                        <p class="text-2xl font-bold text-amber-400" x-text="stats.pedidos_pendentes || 0"></p>
                        <p class="text-xs text-slate-500 mt-1">Aguardando envio</p>
                    </div>

                    <!-- Anúncios Ativos -->
                    <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-slate-400 text-sm">Anúncios Ativos</span>
                            <i class="fas fa-store text-blue-400"></i>
                        </div>
                        <p class="text-2xl font-bold text-blue-400" x-text="stats.anuncios_ativos || 0"></p>
                        <p class="text-xs text-slate-500 mt-1">No marketplace</p>
                    </div>

                    <!-- NF-es Recebidas -->
                    <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-slate-400 text-sm">NF-es Hoje</span>
                            <i class="fas fa-file-invoice text-purple-400"></i>
                        </div>
                        <p class="text-2xl font-bold text-purple-400" x-text="stats.nfes_hoje || 0"></p>
                        <p class="text-xs text-slate-500 mt-1">Recebidas hoje</p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <a href="/products/create-alpine" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-4 border border-slate-700 flex items-center gap-3 transition">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                            <i class="fas fa-plus text-emerald-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Novo Produto</p>
                            <p class="text-xs text-slate-500">Cadastrar</p>
                        </div>
                    </a>

                    <a href="/integrations/anuncios" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-4 border border-slate-700 flex items-center gap-3 transition">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                            <i class="fas fa-tags text-blue-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Anúncios</p>
                            <p class="text-xs text-slate-500">Gerenciar</p>
                        </div>
                    </a>

                    <a href="/orders" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-4 border border-slate-700 flex items-center gap-3 transition">
                        <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                            <i class="fas fa-shopping-cart text-amber-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Pedidos</p>
                            <p class="text-xs text-slate-500">Ver todos</p>
                        </div>
                    </a>

                    <a href="/fiscal/monitor" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-4 border border-slate-700 flex items-center gap-3 transition">
                        <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                            <i class="fas fa-file-invoice text-purple-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">NF-e</p>
                            <p class="text-xs text-slate-500">Recebidas</p>
                        </div>
                    </a>
                </div>

                <!-- Recent Orders -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
                    <div class="p-4 border-b border-slate-700 flex items-center justify-between">
                        <h3 class="font-bold">Últimos Pedidos</h3>
                        <a href="/orders" class="text-indigo-400 text-sm hover:text-indigo-300">Ver todos</a>
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
                                <template x-for="order in recentOrders" :key="order.id">
                                    <tr class="hover:bg-slate-700/30">
                                        <td class="px-4 py-3 font-mono text-sm" x-text="order.external_id"></td>
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
                                        <td class="px-4 py-3 text-right font-bold text-emerald-400" x-text="order.valor_formatado"></td>
                                    </tr>
                                </template>
                                <tr x-show="recentOrders.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                        <i class="fas fa-inbox text-3xl mb-2 opacity-50"></i>
                                        <p>Nenhum pedido recente</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
    function app() {
        return {
            empresaId: '6',
            userName: 'Robson',
            pageTitle: 'Dashboard',
            
            stats: {
                pedidos_pendentes: 5,
                anuncios_ativos: 0,
                nfes_hoje: 3
            },
            
            recentOrders: [],
            
            menu: [
                { name: 'Dashboard', url: '/dashboard', icon: 'fas fa-home' },
                { name: 'Produtos', url: '/products', icon: 'fas fa-box' },
                { name: 'Anúncios', url: '/integrations/anuncios', icon: 'fas fa-store', badge: '12' },
                { name: 'Pedidos', url: '/orders', icon: 'fas fa-shopping-cart', badge: '3' },
                { name: 'Estoque', url: '/estoque', icon: 'fas fa-warehouse' },
                { name: 'NF-e', url: '/fiscal/monitor', icon: 'fas fa-file-invoice' },
                { name: 'Financeiro', url: '/financial', icon: 'fas fa-dollar-sign' },
                { name: 'Integrações', url: '/integrations', icon: 'fas fa-plug' },
                { name: 'Relatórios', url: '/relatorios', icon: 'fas fa-chart-bar' },
                { name: 'Configurações', url: '/admin/configuracoes', icon: 'fas fa-cog' },
            ],
            
            init() {
                this.loadStats();
                this.loadRecentOrders();
            },
            
            async loadStats() {
                try {
                    const response = await fetch(`/api/dashboard/stats?empresa_id=${this.empresaId}`);
                    if (response.ok) {
                        this.stats = await response.json();
                    }
                } catch (e) {
                    console.log('Using default stats');
                }
            },
            
            async loadRecentOrders() {
                try {
                    const response = await fetch(`/api/orders/recent?empresa_id=${this.empresaId}`);
                    if (response.ok) {
                        const data = await response.json();
                        this.recentOrders = data.map(o => ({
                            ...o,
                            valor_formatado: 'R$ ' + parseFloat(o.valor_total || 0).toFixed(2).replace('.', ',')
                        }));
                    }
                } catch (e) {
                    console.log('No recent orders');
                }
            },
            
            changeEmpresa() {
                this.loadStats();
                this.loadRecentOrders();
            }
        }
    }
    </script>
</body>
</html>
