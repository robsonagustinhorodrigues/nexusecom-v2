<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NexusEcom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-dark-950 min-h-screen" x-data="loginForm()">

    <div class="min-h-screen flex">
        
        <!-- Left Side - Branding -->
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-indigo-900 via-dark-950 to-dark-950 items-center justify-center p-12 relative overflow-hidden">
            <!-- Background Grid -->
            <div class="absolute top-0 right-0 w-full h-full opacity-10 pointer-events-none">
                <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.1"/></pattern></defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>
            
            <div class="relative z-10 text-center max-w-lg">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-xl mb-8 shadow-2xl">
                    <i class="fas fa-bolt text-4xl text-indigo-500"></i>
                </div>
                <h2 class="text-5xl font-black text-white tracking-tighter mb-4 leading-tight uppercase">
                    Estratégia <br> <span class="text-indigo-500 italic">Evoluída.</span>
                </h2>
                <p class="text-slate-400 text-lg font-medium leading-relaxed">
                    NexusEcom ⚡: Gestão operacional, inteligência fiscal e apuração de lucro real em uma única plataforma.
                </p>
            </div>

            <div class="absolute bottom-10 left-10 text-[10px] font-black tracking-[0.3em] text-slate-600 uppercase">
                Powered by Eliot ⚡
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="flex-1 flex flex-col items-center justify-center p-8 sm:p-12">
            <div class="w-full max-w-md">
                
                <!-- Mobile Logo -->
                <div class="md:hidden text-center mb-10">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600 mb-4">
                        <i class="fas fa-bolt text-2xl text-white"></i>
                    </div>
                    <h1 class="text-3xl font-black text-white tracking-tighter uppercase">NEXUS<span class="text-indigo-500">ECOM</span></h1>
                </div>

                <div class="mb-10 text-center md:text-left">
                    <h3 class="text-2xl font-bold text-white mb-2">Bem-vindo ao Nexus ⚡</h3>
                    <p class="text-slate-500 text-sm font-medium">Insira suas credenciais para gerenciar suas operações.</p>
                </div>

                <!-- Error Message -->
                <div x-show="error" x-transition class="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span x-text="error"></span>
                </div>

                <form @submit.prevent="login()" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">E-mail</label>
                        <input 
                            type="email" 
                            x-model="email"
                            required 
                            autofocus
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-0 focus:bg-white/10 transition-all"
                            placeholder="seu@email.com.br"
                        >
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Senha</label>
                            <a href="/forgot-password" class="text-xs font-bold text-indigo-500 hover:text-indigo-400 uppercase tracking-wider">Esqueceu?</a>
                        </div>
                        <input 
                            type="password" 
                            x-model="password"
                            required 
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-0 focus:bg-white/10 transition-all"
                            placeholder="••••••••"
                        >
                    </div>

                    <div class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            x-model="remember"
                            id="remember"
                            class="w-5 h-5 rounded bg-white/5 border-white/10 text-indigo-600 focus:ring-offset-dark-950 focus:ring-indigo-500"
                        >
                        <label for="remember" class="text-xs font-bold text-slate-400 uppercase tracking-tight">Manter conectado</label>
                    </div>

                    <button 
                        type="submit"
                        :disabled="loading"
                        class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all transform hover:-translate-y-0.5 active:scale-95 uppercase tracking-widest text-sm flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i x-show="loading" class="fas fa-spinner fa-spin"></i>
                        <span x-show="!loading">Acessar Painel</span>
                        <span x-show="loading">Entrando...</span>
                        <i x-show="!loading" class="fas fa-arrow-right text-xs"></i>
                    </button>
                </form>

                <p class="mt-12 text-center text-slate-600 text-xs font-bold tracking-widest md:hidden">
                    NexusEcom ⚡ Powered by Eliot
                </p>
            </div>
        </div>
    </div>

    <script>
    function loginForm() {
        return {
            email: '',
            password: '',
            remember: false,
            loading: false,
            error: '',
            
            async login() {
                this.loading = true;
                this.error = '';
                
                try {
                    const response = await fetch('/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            email: this.email,
                            password: this.password,
                            remember: this.remember
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok) {
                        window.location.href = data.redirect || '/dashboard';
                    } else {
                        this.error = data.message || 'Credenciais inválidas. Tente novamente.';
                    }
                } catch (e) {
                    this.error = 'Erro ao fazer login. Tente novamente.';
                    console.error(e);
                }
                
                this.loading = false;
            }
        }
    }
    </script>
</body>
</html>
