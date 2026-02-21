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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen" x-data="loginApp()" x-init="init()">

    <div class="min-h-screen flex items-center justify-center p-4">
        
        <div class="w-full max-w-md">
            
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 mb-4 shadow-lg shadow-indigo-500/30">
                    <i class="fas fa-bolt text-2xl text-white"></i>
                </div>
                <h1 class="text-3xl font-black text-white tracking-tight">NEXUS<span class="text-indigo-500">ECOM</span></h1>
                <p class="text-slate-400 text-sm mt-1">Gestão Inteligente de E-commerce</p>
            </div>

            <!-- Login Card -->
            <div class="bg-slate-800/50 border border-slate-700 rounded-2xl p-8 backdrop-blur-sm">
                
                <h2 class="text-xl font-bold text-white mb-6 text-center">Acessar Conta</h2>

                <!-- Error -->
                <div x-show="error" x-transition class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm flex items-center gap-2">
                    <i class="fas fa-circle-exclamation"></i>
                    <span x-text="error"></span>
                </div>

                <form action="/login" method="POST" class="space-y-5">
                    @csrf
                    
                    <!-- Error -->
                    @if($errors->any())
                    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm flex items-center gap-2">
                        <i class="fas fa-circle-exclamation"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                    @endif
                    
                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input 
                                type="email" 
                                name="email" value="{{ old('email') }}"
                                required
                                class="w-full bg-slate-900/50 border border-slate-600 rounded-xl pl-11 pr-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-0 transition"
                                placeholder="seu@email.com"
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Senha</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input 
                                type="password" 
                                name="password"
                                required
                                class="w-full bg-slate-900/50 border border-slate-600 rounded-xl pl-11 pr-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-0 transition"
                                placeholder="••••••••"
                            >
                        </div>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-900 border-slate-600 text-indigo-500 focus:ring-0">
                            <span class="text-xs text-slate-400">Lembrar</span>
                        </label>
                        <a href="/forgot-password" class="text-xs text-indigo-400 hover:text-indigo-300">Esqueceu a senha?</a>
                    </div>

                    <!-- Submit -->
                    <button 
                        type="submit"
                        :disabled="loading"
                        class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <i x-show="!loading" class="fas fa-sign-in-alt"></i>
                        <i x-show="loading" class="fas fa-spinner fa-spin"></i>
                        <span x-text="loading ? 'Entrando...' : 'Entrar'"></span>
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <p class="text-center text-slate-600 text-xs mt-8">
                NexusEcom ⚡ Powered by Eliot
            </p>
        </div>
    </div>

    <script>
    function loginApp() {
        return {
            email: '',
            password: '',
            remember: false,
            loading: false,
            error: '',
            
            init() {
                // Handle form submit for loading state
                this.$el.addEventListener('submit', () => {
                    this.loading = true;
                });
            }
        };
    }
    </script>
</body>
</html>
