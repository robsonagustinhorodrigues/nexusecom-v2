<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NexusEcom')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    </style>
    @yield('styles')
</head>
<body class="bg-slate-900 text-white h-full overflow-hidden" x-data="appLayout()" x-init="init()">

    <div class="flex h-full">
        <!-- Sidebar -->
        <aside 
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-800 border-r border-slate-700 transform transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="h-14 flex items-center px-4 border-b border-slate-700">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-bolt text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-lg tracking-tight">NEXUS<span class="text-indigo-500">ECOM</span></span>
                </div>
            </div>

            <nav class="p-3 space-y-1 overflow-y-auto h-[calc(100%-3.5rem)] custom-scrollbar pb-20">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'icon' => 'fa-chart-line', 'label' => 'Dashboard'],
                        ['route' => 'products.index', 'icon' => 'fa-box', 'label' => 'Produtos'],
                        ['route' => 'orders.index', 'icon' => 'fa-shopping-cart', 'label' => 'Pedidos'],
                        ['route' => 'anuncios.index', 'icon' => 'fa-store', 'label' => 'Anúncios'],
                        ['route' => 'estoque.index', 'icon' => 'fa-warehouse', 'label' => 'Estoque'],
                        ['route' => 'fiscal.nfe', 'icon' => 'fa-file-invoice-dollar', 'label' => 'NF-e'],
                        ['route' => 'dre.index', 'icon' => 'fa-chart-pie', 'label' => 'DRE'],
                        ['route' => 'integrations.index', 'icon' => 'fa-plug', 'label' => 'Integrações'],
                        ['route' => 'admin.empresas', 'icon' => 'fa-building', 'label' => 'Empresas'],
                        ['route' => 'admin.depositos', 'icon' => 'fa-boxes-packing', 'label' => 'Depósitos'],
                        ['route' => 'admin.usuarios', 'icon' => 'fa-users', 'label' => 'Usuários'],
                        ['route' => 'admin.avisos', 'icon' => 'fa-bell', 'label' => 'Avisos'],
                        ['route' => 'admin.tarefas', 'icon' => 'fa-tasks', 'label' => 'Tarefas'],
                        ['route' => 'admin.configuracoes', 'icon' => 'fa-cog', 'label' => 'Configurações'],
                        ['route' => 'roadmap', 'icon' => 'fa-map', 'label' => 'Roadmap'],
                    ];
                @endphp
                
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}" 
                       class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs($item['route']) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                        <i class="fas {{ $item['icon'] }} w-5 text-center"></i>
                        <span class="font-medium text-sm">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700 bg-slate-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 px-4 py-2 w-full rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-all font-bold">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span>Sair</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Overlay Mobile -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden" x-cloak></div>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0 h-full relative">
            <!-- Header -->
            <header class="h-14 bg-slate-900/50 backdrop-blur-md border-b border-slate-800 flex items-center justify-between px-4 z-30">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-slate-400 hover:text-white">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="font-bold text-lg text-white">@yield('header_title', 'NexusEcom')</h1>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Notifications Bell -->
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button @click="notifOpen = !notifOpen" class="p-2 text-slate-400 hover:text-white relative">
                            <i class="fas fa-bell"></i>
                            <span x-show="notifCount > 0" x-text="notifCount" class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 rounded-full text-[9px] font-bold text-white flex items-center justify-center"></span>
                        </button>
                        <div x-show="notifOpen" @click.away="notifOpen = false" x-transition x-cloak 
                             class="absolute right-0 mt-2 w-80 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden">
                            <div class="p-3 border-b border-slate-700 flex justify-between items-center">
                                <span class="font-bold text-white text-sm">Notificações</span>
                                <a href="/admin/avisos" class="text-xs text-indigo-400 hover:text-indigo-300">Ver todas</a>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <template x-for="n in notifications" :key="n.id">
                                    <div class="p-3 border-b border-slate-700/50 hover:bg-slate-700/30">
                                        <div class="flex gap-3">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                                 :class="n.type === 'success' ? 'bg-emerald-500/20 text-emerald-400' : n.type === 'error' ? 'bg-rose-500/20 text-rose-400' : 'bg-indigo-500/20 text-indigo-400'">
                                                <i :class="n.type === 'success' ? 'fa-check' : n.type === 'error' ? 'fa-times' : 'fa-info'" class="fas text-xs"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-white font-bold truncate" x-text="n.title"></p>
                                                <p class="text-[10px] text-slate-400 truncate" x-text="n.message"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="notifications.length === 0" class="p-6 text-center text-slate-500 text-xs">
                                    Nenhuma notificação
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks Icon -->
                    <a href="/admin/tarefas" class="p-2 text-slate-400 hover:text-white relative">
                        <i class="fas fa-tasks"></i>
                    </a>

                    <div class="h-6 w-px bg-slate-700 mx-1"></div>

                    <!-- Seletor de Empresa (Alpine Version) -->
                    <select x-model="empresaId" @change="changeEmpresa()" 
                            class="bg-slate-800 border border-slate-700 rounded-lg px-3 py-1.5 text-xs font-bold text-indigo-400 outline-none focus:border-indigo-500">
                        <option value="4">MaxLider</option>
                        <option value="5">LideraMais</option>
                        <option value="6">LideraMix</option>
                    </select>

                    <div class="h-6 w-px bg-slate-700 mx-1"></div>

                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 p-1 hover:bg-slate-800 rounded-lg transition-all">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-sm font-bold text-white shadow-lg">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <i class="fas fa-chevron-down text-[10px] text-slate-500"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition x-cloak 
                             class="absolute right-0 mt-2 w-48 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-700 mb-2">
                                <p class="text-xs font-bold text-white">{{ Auth::user()->name }}</p>
                                <p class="text-[10px] text-slate-500 truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-slate-400 hover:bg-rose-500/10 hover:text-rose-400 flex items-center gap-2 transition-all">
                                    <i class="fas fa-sign-out-alt text-xs"></i> Sair do Sistema
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto custom-scrollbar bg-slate-900/50 p-4 lg:p-6">
                @yield('content')
                {{ $slot ?? '' }}
            </main>
        </div>
    </div>

    <script>
    function appLayout() {
        return {
            sidebarOpen: window.innerWidth >= 1024,
            empresaId: localStorage.getItem('empresa_id') || '4',
            notifications: [],
            notifCount: 0,
            
            init() {
                // Ensure localStorage has a value on first load
                if (!localStorage.getItem('empresa_id')) {
                    localStorage.setItem('empresa_id', '4');
                }
                this.empresaId = localStorage.getItem('empresa_id') || '4';
                
                window.addEventListener('resize', () => {
                    if(window.innerWidth >= 1024) this.sidebarOpen = true;
                });
                this.$watch('empresaId', (val) => {
                    localStorage.setItem('empresa_id', val);
                });
                this.loadNotifications();
                // Refresh notifications every 30 seconds
                setInterval(() => this.loadNotifications(), 30000);
            },

            changeEmpresa() {
                window.dispatchEvent(new CustomEvent('empresa-changed', { 
                    detail: this.empresaId 
                }));
            },

            async loadNotifications() {
                try {
                    const res = await fetch('/api/admin/notificacoes');
                    if (res.ok) {
                        const data = await res.json();
                        this.notifications = data.notificacoes || [];
                        this.notifCount = this.notifications.filter(n => !n.read).length;
                    }
                } catch (e) { console.error(e); }
            }
        }
    }
    </script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>
