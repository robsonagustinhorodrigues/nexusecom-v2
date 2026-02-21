<x-guest-layout>
    <div class="min-h-screen flex flex-col md:flex-row bg-dark-950">
        
        <!-- Lado Esquerdo: Branding & Impacto -->
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-indigo-900 via-dark-950 to-dark-950 items-center justify-center p-12 relative overflow-hidden">
            <!-- Background Decorations -->
            <div class="absolute top-0 right-0 w-full h-full opacity-10 pointer-events-none">
                <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.1"/></pattern></defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>
            
            <div class="relative z-10 text-center max-w-lg">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl mb-8 shadow-2xl">
                    <img src="/logo-nexus.svg" class="w-14 h-14" alt="Nexus Logo">
                </div>
                <h2 class="text-5xl font-black text-white tracking-tighter mb-4 leading-tight uppercase">
                    Estratégia <br> <span class="text-indigo-500 italic">Evoluída.</span>
                </h2>
                <p class="text-slate-400 text-lg font-medium leading-relaxed">
                    NexusEcom ⚡: Gestão operacional, inteligência fiscal e apuração de lucro real em uma única plataforma.
                </p>
            </div>

            <!-- Footer Branding -->
            <div class="absolute bottom-10 left-10 text-[10px] font-black tracking-[0.3em] text-slate-600 uppercase">
                Powered by Eliot ⚡ Engine v1.0
            </div>
        </div>

        <!-- Lado Direito: Formulário de Elite -->
        <div class="flex-1 flex flex-col items-center justify-center p-8 sm:p-12">
            <div class="w-full max-w-md">
                <div class="md:hidden text-center mb-10">
                     <img src="/logo-nexus.svg" class="w-16 h-16 mx-auto mb-4" alt="Nexus Logo">
                     <h1 class="text-3xl font-black text-white tracking-tighter uppercase">NEXUS<span class="text-indigo-500">ECOM</span></h1>
                </div>

                <div class="mb-10 text-center md:text-left">
                    <h3 class="text-2xl font-bold text-white mb-2">Bem-vindo ao Nexus ⚡</h3>
                    <p class="text-slate-500 text-sm font-medium">Insira suas credenciais para gerenciar suas operações.</p>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2 px-1">E-mail de Acesso</label>
                        <input id="email" type="email" name="email" :value="old('email')" required autofocus class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-indigo-500 focus:ring-0 focus:bg-white/10 transition-all placeholder:text-slate-700" placeholder="seu@email.com.br" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2 px-1">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Senha de Segurança</label>
                            @if (Route::has('password.request'))
                                <a class="text-[10px] font-black text-indigo-500 hover:text-indigo-400 uppercase tracking-widest transition-colors" href="{{ route('password.request') }}">Esqueceu?</a>
                            @endif
                        </div>
                        <input id="password" type="password" name="password" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-indigo-500 focus:ring-0 focus:bg-white/10 transition-all placeholder:text-slate-700" placeholder="••••••••" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3 px-1">
                        <input id="remember_me" type="checkbox" name="remember" class="w-5 h-5 rounded-lg bg-white/5 border-white/10 text-indigo-600 focus:ring-offset-dark-950 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-tight">Manter conectado</span>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-2xl shadow-indigo-600/30 transition-all transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-sm flex items-center justify-center gap-2">
                            Acessar Painel <i class="fas fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </form>

                <p class="mt-12 text-center text-slate-600 text-[10px] font-black tracking-widest uppercase md:hidden">
                    NexusEcom Engine v1.0 • Eliot ⚡
                </p>
            </div>
        </div>

    </div>
</x-guest-layout>
