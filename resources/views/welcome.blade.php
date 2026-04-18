<!DOCTYPE html>
<html lang="pt-br" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusEcom ⚡ - Gestão Inteligente</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,800,900" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .nexus-gradient { background: linear-gradient(135deg, #0f172a 0%, #020617 100%); }
        .text-glow { text-shadow: 0 0 20px rgba(99, 102, 241, 0.4); }
    </style>
</head>
<body class="nexus-gradient h-full text-slate-200 overflow-hidden flex flex-col items-center justify-center">

    <!-- Background Decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-emerald-600/5 rounded-full blur-3xl"></div>
    </div>

    <main class="relative z-10 text-center px-6">
        <div class="mb-8 inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-indigo-600/10 border border-indigo-500/20 shadow-2xl text-4xl">
            ⚡
        </div>

        <h1 class="text-6xl md:text-8xl font-black tracking-tighter text-white mb-4 text-glow">
            NEXUS<span class="text-indigo-500">ECOM</span>
        </h1>
        
        <p class="text-slate-400 text-lg md:text-xl max-w-2xl mx-auto mb-12 font-medium leading-relaxed">
            A próxima geração em gestão de e-commerce. <br>
            <span class="text-indigo-400 font-bold">Lucratividade Real</span>, <span class="text-emerald-400 font-bold">Automação Fiscal</span> e <span class="text-amber-400 font-bold">Inteligência Eliot</span>.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/') }}" class="w-full sm:w-auto px-10 py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-xl shadow-indigo-600/20 transition-all transform hover:-translate-y-1">
                        ACESSAR DASHBOARD
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full sm:w-auto px-10 py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-xl shadow-indigo-600/20 transition-all transform hover:-translate-y-1">
                        ENTRAR NO SISTEMA
                    </a>
                    
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="w-full sm:w-auto px-10 py-4 bg-dark-900 border border-dark-800 text-slate-400 font-bold rounded-2xl hover:bg-dark-800 transition-all">
                            CRIAR CONTA
                        </a>
                    @endif
                @endauth
            @endif
        </div>

        <div class="mt-16 flex items-center justify-center gap-8 opacity-40 grayscale hover:grayscale-0 transition-all">
            <i class="fab fa-amazon text-2xl"></i>
            <i class="fas fa-shopping-bag text-2xl"></i>
            <i class="fas fa-bolt text-2xl"></i>
            <i class="fas fa-truck text-2xl"></i>
        </div>
    </main>

    <footer class="absolute bottom-8 text-slate-600 text-xs font-bold tracking-widest uppercase">
        NexusEcom Engine v1.0 • Built by Eliot ⚡
    </footer>

</body>
</html>
