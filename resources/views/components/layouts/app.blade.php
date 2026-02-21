<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" x-data="{ darkMode: localStorage.getItem('darkMode') !== 'false' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="NexusEcom - Sistema de Gestão Empresarial">
    <meta name="theme-color" content="#4f46e5">
    <title>NexusEcom ⚡</title>
    
    <!-- Preconnect para性能 -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- DNS Prefetch -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    
    <!-- Fonts com display swap para performance -->
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />
    
    <!-- Font Awesome (ícones) - versão minimal -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{--
        REGRA DE OURO — LIVEWIRE 3:
        1. NÃO adicione CDN do Alpine.js (Livewire 3 já inclui)
        2. NÃO use @livewireStyles / @livewireScripts (obsoletos no LW3)
        3. Tailwind é compilado pelo Vite via @tailwind directives em app.css
    --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
        
        /* Loading shimmer animation */
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .animate-shimmer {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        /* Performance: contenção de layout */
        .layout-stable { contain: layout style; }
        
        /* Hover mais rápido */
        .nav-item { transition: all 0.15s ease-out; }
    </style>
    
    <!-- Prefetch rotas comuns -->
    <link rel="prefetch" href="{{ route('dashboard') }}">
    <link rel="prefetch" href="{{ route('fiscal.monitor') }}">
</head>
<body class="h-full bg-slate-50 dark:bg-dark-950 text-slate-800 dark:text-slate-200 antialiased overflow-hidden" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside 
            class="relative flex flex-col flex-shrink-0 transition-all duration-200 bg-white dark:bg-dark-900 border-r border-slate-200 dark:border-dark-800 shadow-xl"
            :class="sidebarOpen ? 'w-64' : 'w-[72px]'"
            x-data="{ hoverItem: null }"
        >
            <!-- Logo Area -->
            <div class="flex items-center h-14 px-3 border-b border-slate-100 dark:border-dark-800">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-md">
                    <i class="fas fa-bolt text-white text-sm"></i>
                </div>
                <div class="flex items-center justify-between flex-1 overflow-hidden ml-3" x-show="sidebarOpen" x-transition.duration.200>
                    <span class="font-bold text-base tracking-tight text-slate-800 dark:text-white">Nexus<span class="text-indigo-500">Ecom</span></span>
                    <span class="text-[10px] font-bold text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-1.5 py-0.5 rounded">PRO</span>
                </div>
                <button 
                    @click="sidebarOpen = !sidebarOpen" 
                    class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-dark-800 text-slate-400 transition-colors"
                    x-show="!sidebarOpen"
                >
                    <i class="fas fa-bars text-sm"></i>
                </button>
                <button 
                    @click="sidebarOpen = !sidebarOpen" 
                    class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-dark-800 text-slate-400 transition-colors ml-auto"
                    x-show="sidebarOpen"
                >
                    <i class="fas fa-angle-left text-sm"></i>
                </button>
            </div>

            <!-- Navigation Items -->
            <nav class="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto custom-scrollbar">
                @php
                    $navItems = [
                        // Principal
                        ['route' => 'dashboard', 'icon' => 'fa-chart-line', 'label' => 'Dashboard', 'section' => 'Principal'],
                        
                        // Operação
                        ['route' => 'products.index', 'icon' => 'fa-box', 'label' => 'Produtos', 'section' => 'Operação'],
                        ['route' => 'orders.index', 'icon' => 'fa-shopping-cart', 'label' => 'Pedidos', 'section' => null],
                        ['route' => 'wms.index', 'icon' => 'fa-boxes-packing', 'label' => 'Estoque', 'section' => null],
                        ['route' => 'financial.dashboard', 'icon' => 'fa-coins', 'label' => 'Lucro Real', 'section' => null],
                        ['route' => 'dre.index', 'icon' => 'fa-chart-pie', 'label' => 'DRE', 'section' => null],
                        ['route' => 'finances.despesas', 'icon' => 'fa-receipt', 'label' => 'Despesas', 'section' => null],
                        
                        // Fiscal - com submenu
                        ['section' => 'Fiscal'],
                        ['submenu' => [
                            ['route' => 'fiscal.monitor', 'icon' => 'fa-file-invoice-dollar', 'label' => 'NF-e Monitor'],
                            ['route' => 'fiscal.skus', 'icon' => 'fa-barcode', 'label' => 'Associação SKUs'],
                            ['route' => 'fiscal.relatorio.faturamento', 'icon' => 'fa-chart-bar', 'label' => 'Faturamento 12M'],
                            ['route' => 'fiscal.relatorio.simples', 'icon' => 'fa-calculator', 'label' => 'Simples Nacional'],
                        ], 'section' => null],
                        
                        // Integrações
                        ['route' => 'integrations.index', 'icon' => 'fa-plug', 'label' => 'Integrações', 'section' => 'Integração'],
                        ['submenu' => [
                            ['route' => 'integrations.parceiros', 'icon' => 'fa-handshake', 'label' => 'Parceiros'],
                            ['route' => 'integrations.anuncios', 'icon' => 'fa-bullhorn', 'label' => 'Anúncios'],
                        ], 'section' => null],

                        // Ferramentas
                        ['submenu' => [
                            ['route' => 'tools.zpl', 'icon' => 'fa-print', 'label' => 'Impressora ZPL'],
                            ['route' => 'tools.ean', 'icon' => 'fa-barcode', 'label' => 'Gerador EAN'],
                        ], 'section' => 'Ferramentas'],
                        
                        // Admin/Configurações
                        ['route' => 'admin.empresas', 'icon' => 'fa-building', 'label' => 'Empresas', 'section' => 'Administração'],
                        ['route' => 'admin.armazens', 'icon' => 'fa-warehouse', 'label' => 'Armazéns', 'section' => null],
                        ['route' => 'admin.usuarios', 'icon' => 'fa-users', 'label' => 'Usuários', 'section' => null],
                        ['route' => 'admin.configuracoes', 'icon' => 'fa-cog', 'label' => 'Configurações', 'section' => null],
                        
                        // Projeto
                        ['route' => 'roadmap', 'icon' => 'fa-map', 'label' => 'Implementação', 'section' => 'Projeto'],
                    ];
                    
                    $lastSection = null;
                @endphp
                
                @foreach($navItems as $item)
                    @if(isset($item['section']) && $item['section'] && $lastSection !== $item['section'])
                        <div class="pt-3 pb-1.5" x-show="sidebarOpen">
                            <span class="px-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">{{ $item['section'] }}</span>
                        </div>
                        @php $lastSection = $item['section']; @endphp
                    @endif
                    
                    @if(isset($item['route']) && $item['route'])
                        <a 
                            href="{{ route($item['route']) }}" 
                            class="nav-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all relative group"
                            :class="hoverItem === '{{ $item['route'] }}' ? 'bg-slate-100 dark:bg-dark-800' : '{{ request()->routeIs($item['route']) ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-dark-800 hover:text-slate-900 dark:hover:text-white' }}'"
                            @mouseenter="hoverItem = '{{ $item['route'] }}'"
                            @mouseleave="hoverItem = null"
                            title="{{ $item['label'] }}"
                        >
                            <i class="fas {{ $item['icon'] }} w-5 text-center text-[15px] {{ request()->routeIs($item['route']) ? 'text-indigo-500' : '' }}"></i>
                            <span x-show="sidebarOpen" x-transition.duration.150 class="truncate">{{ $item['label'] }}</span>
                            
                            @if(request()->routeIs($item['route']))
                                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-4 bg-indigo-500 rounded-r-full"></div>
                            @endif
                        </a>
                    @elseif(isset($item['submenu']) && $item['submenu'])
                        <div x-show="sidebarOpen" x-collapse class="mt-1 space-y-1">
                            @foreach($item['submenu'] as $submenuItem)
                            <a 
                                href="{{ route($submenuItem['route']) }}" 
                                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all"
                                :class="'{{ request()->routeIs($submenuItem['route']) ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-dark-800 hover:text-slate-900 dark:hover:text-white' }}'"
                                title="{{ $submenuItem['label'] }}"
                            >
                                <i class="fas {{ $submenuItem['icon'] }} w-4 text-center text-xs"></i>
                                <span class="truncate">{{ $submenuItem['label'] }}</span>
                            </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </nav>

            <!-- Bottom Section -->
            <div class="p-2 border-t border-slate-100 dark:border-dark-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button 
                        type="submit" 
                        class="nav-item w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/5 transition-all"
                        title="Sair do sistema"
                    >
                        <i class="fas fa-sign-out-alt w-5 text-center"></i>
                        <span x-show="sidebarOpen" x-transition.duration.150>Sair</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 flex flex-col relative bg-slate-50 dark:bg-dark-950 overflow-hidden">
            
            <!-- Header / Topbar -->
            <header class="sticky top-0 z-[100] h-16 border-b border-slate-200 dark:border-dark-800 bg-white/80 dark:bg-dark-900/80 flex items-center justify-between px-6 backdrop-blur-md">
                <div class="flex items-center gap-4">
                    <!-- Breadcrumb简单 -->
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-slate-400 font-medium">Nexus</span>
                        <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
                        <span class="text-slate-900 dark:text-white font-semibold capitalize">
                            @php
                                $routeName = request()->route()->getName();
                                echo str_replace(['.', '-', '_'], ' ', $routeName);
                            @endphp
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Empresa Selector -->
                    <livewire:components.layouts.company-selector />

                    <div class="h-6 w-[1px] bg-slate-200 dark:bg-dark-700"></div>

                    <!-- Dark Mode Toggle -->
                    <button 
                        @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
                        class="w-9 h-9 rounded-lg border border-slate-200 dark:border-dark-700 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-dark-800 transition-all"
                        title="Alternar tema"
                    >
                        <i class="fas text-sm" :class="darkMode ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-500'"></i>
                    </button>
                    
                    <!-- Notifications -->
                    <a href="{{ route('admin.avisos') }}" class="relative w-9 h-9 rounded-lg border border-slate-200 dark:border-dark-700 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-dark-800 transition-all" title="Avisos">
                        <i class="far fa-bell text-sm"></i>
                        @php
                            $naoLidas = \App\Models\Notificacao::where('user_id', auth()->id())->where('lida', false)->count();
                        @endphp
                        @if($naoLidas > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold">{{ $naoLidas > 9 ? '9+' : $naoLidas }}</span>
                        @endif
                    </a>

                    <!-- Tarefas (apenas admin/master) -->
                    @auth
                    <a href="{{ route('admin.tarefas') }}" class="relative w-9 h-9 rounded-lg border border-slate-200 dark:border-dark-700 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-dark-800 transition-all" title="Tarefas">
                        <i class="fas fa-tasks text-sm"></i>
                        @php
                            $tarefasAtivas = \App\Models\Tarefa::where('user_id', auth()->id())->where('status', 'processando')->count();
                        @endphp
                        @if($tarefasAtivas > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-indigo-500 rounded-full text-[10px] text-white flex items-center justify-center font-bold">{{ $tarefasAtivas > 9 ? '9+' : $tarefasAtivas }}</span>
                        @endif
                    </a>
                    @endauth

                    <!-- Quick Actions -->
                    <div class="h-8 w-[1px] bg-slate-200 dark:bg-dark-700 mx-1"></div>

                    <!-- User Menu -->
                    <div class="flex items-center gap-2 pl-1">
                        <div class="text-right hidden md:block">
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ Auth::user()->name ?? 'Usuário' }}</p>
                            <p class="text-[10px] text-slate-400 font-medium">{{ Auth::user()->current_empresa_id ? 'Administrador' : 'Usuário' }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-indigo-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-md ring-2 ring-white dark:ring-dark-800">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content Container -->
            <main class="flex-1 flex flex-col relative bg-slate-50 dark:bg-dark-950 overflow-hidden">
                <!-- Page Content -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden p-6 custom-scrollbar relative z-0">
                    <div class="max-w-7xl mx-auto">
                        {{ $slot ?? '' }}
                    </div>
                </div>
            </main>

    </div>


</body>
</html>
