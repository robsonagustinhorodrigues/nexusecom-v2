<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="NexusEcom - Sistema de Gestão Empresarial">
    <title>{{ $title ?? 'NexusEcom' }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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

        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-900 text-white antialiased overflow-hidden" x-data="appLayout()" x-init="init()">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside 
            class="fixed left-0 top-14 bottom-0 w-64 bg-slate-800 border-r border-slate-700 transform transition-transform z-40 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <nav class="p-3 space-y-1 overflow-y-auto h-full pb-20 custom-scrollbar">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'icon' => 'fa-chart-line', 'label' => 'Dashboard'],
                        ['route' => 'products.index', 'icon' => 'fa-box', 'label' => 'Produtos'],
                        ['route' => 'orders.index', 'icon' => 'fa-shopping-cart', 'label' => 'Pedidos'],
                        ['route' => 'anuncios.index', 'icon' => 'fa-store', 'label' => 'Anúncios'],
                        ['route' => 'estoque.index', 'icon' => 'fa-warehouse', 'label' => 'Estoque'],
                        ['route' => 'wms.index', 'icon' => 'fa-boxes-packing', 'label' => 'Armazéns'],
                        ['route' => 'fiscal.nfe', 'icon' => 'fa-file-invoice-dollar', 'label' => 'NF-e'],
                        ['route' => 'dre.index', 'icon' => 'fa-chart-pie', 'label' => 'DRE'],
                        ['route' => 'integrations.index', 'icon' => 'fa-plug', 'label' => 'Integrações'],
                        ['route' => 'admin.empresas', 'icon' => 'fa-building', 'label' => 'Empresas'],
                        ['route' => 'admin.usuarios', 'icon' => 'fa-users', 'label' => 'Usuários'],
                        ['route' => 'admin.configuracoes', 'icon' => 'fa-cog', 'label' => 'Configurações'],
                        ['route' => 'roadmap', 'icon' => 'fa-map', 'label' => 'Projeto'],
                    ];
                @endphp
                
                @foreach($navItems as $item)
                    <a 
                        href="{{ route($item['route']) }}" 
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all {{ request()->routeIs($item['route']) ? 'bg-indigo-600/20 text-indigo-400' : 'text-slate-400 hover:bg-slate-700 hover:text-white' }}"
                    >
                        <i class="fas {{ $item['icon'] }} w-5"></i>
                        <span class="font-medium text-sm">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <!-- Bottom Section -->
            <div class="absolute bottom-0 left-0 right-0 p-4 bg-slate-800 border-t border-slate-700">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 px-4 py-2 w-full rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-all">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span class="font-medium text-sm">Sair</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Header / Topbar -->
            <header class="fixed top-0 left-0 right-0 h-14 bg-slate-900/95 backdrop-blur border-b border-slate-800 z-50 flex items-center justify-between px-4 lg:left-64">
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
                            placeholder="Buscar..."
                            class="w-full bg-slate-800 border border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        >
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Empresa Selector -->
                    @livewire('components.layouts.company-selector')

                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 pl-3 border-l border-slate-700 pr-2 py-1 rounded-lg hover:bg-slate-800 transition">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-bold">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-transition x-cloak class="absolute right-0 mt-2 w-48 bg-slate-800 border border-slate-700 rounded-lg shadow-lg py-1 z-50">
                            <div class="px-4 py-2 border-b border-slate-700">
                                <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-slate-400">{{ Auth::user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-slate-300 hover:bg-slate-700 hover:text-red-400 flex items-center gap-2">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 pt-14 lg:pl-0 overflow-y-auto custom-scrollbar">
                <div class="p-6">
                    {{ $slot ?? '' }}
                </div>
            </main>
        </div>
    </div>

    <script>
    function appLayout() {
        return {
            sidebarOpen: false,
            empresaId: localStorage.getItem('empresa_id') || '6',
            search: '',
            
            init() {
                this.$watch('empresaId', (value) => {
                    localStorage.setItem('empresa_id', value);
                });
            },
            
            performSearch() {
                if (this.search.trim()) {
                    window.location.href = `/products?search=${encodeURIComponent(this.search)}`;
                }
            }
        }
    }
    </script>
    @stack('scripts')
</body>
</html>
