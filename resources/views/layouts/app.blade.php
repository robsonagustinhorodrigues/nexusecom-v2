<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" x-data="{ darkMode: localStorage.getItem('darkMode') !== 'false' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NexusEcom ⚡</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet" />
    
    <!-- Font Awesome (ícones) -->
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
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-dark-950 text-slate-800 dark:text-slate-200 antialiased overflow-hidden" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside 
            class="relative flex flex-col flex-shrink-0 transition-all duration-300 bg-white dark:bg-dark-900 border-r border-slate-200 dark:border-dark-800 shadow-xl z-20"
            :class="sidebarOpen ? 'w-64' : 'w-20'"
        >
            <div class="flex items-center justify-between h-16 px-4 border-b border-slate-200 dark:border-dark-800">
                <div class="flex items-center gap-2 overflow-hidden" x-show="sidebarOpen" x-transition>
                    <img src="/logo-nexus.svg" class="w-8 h-8" alt="Logo">
                    <span class="font-black text-xl tracking-tighter text-slate-900 dark:text-white uppercase italic">Nexus<span class="text-indigo-600 dark:text-indigo-500">Ecom</span></span>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-800 text-slate-400">
                    <i class="fas" :class="sidebarOpen ? 'fa-angle-double-left' : 'fa-bars text-xl'"></i>
                </button>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 {{ request()->routeIs('dashboard') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-dark-800' }} rounded-xl font-bold transition-all shadow-sm">
                    <i class="fas fa-chart-line w-6 text-center"></i>
                    <span x-show="sidebarOpen" x-transition>Dashboard</span>
                </a>
                
                <a href="{{ route('admin.empresas') }}" class="flex items-center gap-3 px-3 py-2 {{ request()->routeIs('admin.empresas') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-dark-800' }} rounded-xl font-bold transition-all shadow-sm">
                    <i class="fas fa-building w-6 text-center"></i>
                    <span x-show="sidebarOpen" x-transition>Empresas</span>
                </a>

                <a href="{{ route('integrations.index') }}" class="flex items-center gap-3 px-3 py-2 {{ request()->routeIs('integrations.*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-dark-800' }} rounded-xl font-bold transition-all shadow-sm">
                    <i class="fas fa-plug w-6 text-center"></i>
                    <span x-show="sidebarOpen" x-transition>Integrações</span>
                </a>

                <a href="{{ route('admin.usuarios') }}" class="flex items-center gap-3 px-3 py-2 {{ request()->routeIs('admin.usuarios') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-dark-800' }} rounded-xl font-bold transition-all shadow-sm">
                    <i class="fas fa-users w-6 text-center"></i>
                    <span x-show="sidebarOpen" x-transition>Equipe</span>
                </a>
            </nav>

            <div class="p-4 border-t border-slate-200 dark:border-dark-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-slate-500 hover:text-rose-500 hover:bg-rose-500/5 rounded-xl font-bold transition-all">
                        <i class="fas fa-power-off w-6 text-center"></i>
                        <span x-show="sidebarOpen" x-transition>Sair</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN AREA -->
        <main class="flex-1 flex flex-col bg-white dark:bg-dark-950 overflow-hidden relative">
            <header class="h-16 border-b border-slate-200 dark:border-dark-800 bg-white/50 dark:bg-dark-900/50 flex items-center justify-between px-8 backdrop-blur-md relative z-50">
                <div class="flex items-center gap-4">
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="w-10 h-10 rounded-xl border border-slate-200 dark:border-dark-700 flex items-center justify-center text-slate-500">
                        <i class="fas" :class="darkMode ? 'fa-sun text-amber-500' : 'fa-moon text-indigo-600'"></i>
                    </button>
                    <!-- INDICADOR DE LIVEWIRE ATIVO -->
                    <div id="livewire-status" class="w-2 h-2 rounded-full bg-rose-500" title="Livewire Offline"></div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-emerald-500 flex items-center justify-center text-white font-black text-sm">R</div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar relative z-10">
                {{ $slot }}
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('livewire:navigated', () => { 
            document.getElementById('livewire-status').classList.remove('bg-rose-500');
            document.getElementById('livewire-status').classList.add('bg-emerald-500');
            document.getElementById('livewire-status').title = 'Livewire Online';
        });
        window.addEventListener('load', () => {
            if(window.Livewire) {
                document.getElementById('livewire-status').classList.remove('bg-rose-500');
                document.getElementById('livewire-status').classList.add('bg-emerald-500');
                document.getElementById('livewire-status').title = 'Livewire Online';
            }
        });
    </script>
</body>
</html>
