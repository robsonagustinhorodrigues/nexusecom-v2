<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: false);
    }
}; ?>

<div class="flex min-h-screen bg-dark-950 overflow-hidden text-slate-200">
    <!-- Lado Esquerdo: Branding (DNA NEXUS) -->
    <div class="hidden lg:flex lg:w-[55%] bg-gradient-to-br from-indigo-900 via-dark-950 to-dark-950 items-center justify-center p-16 relative">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <svg width="100%" height="100%"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid)" /></svg>
        </div>
        
        <div class="relative z-10 text-center max-w-lg">
            <div class="inline-flex items-center justify-center w-28 h-28 rounded-[2.5rem] bg-white/5 border border-white/10 backdrop-blur-2xl mb-10 shadow-2xl">
                <img src="/logo-nexus.svg" class="w-16 h-16" alt="Nexus Logo">
            </div>
            <h2 class="text-6xl font-black text-white tracking-tighter mb-6 leading-[0.9] uppercase">
                Estratégia <br> <span class="text-indigo-500 italic">Evoluída.</span>
            </h2>
            <p class="text-slate-400 text-lg font-medium leading-relaxed opacity-80">
                NexusEcom ⚡: Gestão operacional de elite, inteligência fiscal e apuração de lucro real.
            </p>
        </div>

        <div class="absolute bottom-12 left-12 text-[10px] font-black tracking-[0.4em] text-slate-600 uppercase">
            NexusEcom v1.0 • Eliot ⚡ Engine
        </div>
    </div>

    <!-- Lado Direito: Formulário Centralizado -->
    <div class="flex-1 flex flex-col items-center justify-center p-6 sm:p-12 lg:p-20">
        <div class="w-full max-w-[420px] space-y-10">
            
            <!-- Mobile Header -->
            <div class="lg:hidden text-center">
                 <img src="/logo-nexus.svg" class="w-16 h-16 mx-auto mb-4" alt="Nexus Logo">
                 <h1 class="text-3xl font-black text-white tracking-tighter uppercase">NEXUS<span class="text-indigo-500">ECOM</span></h1>
            </div>

            <div class="text-center lg:text-left">
                <h3 class="text-3xl font-black text-white mb-2 tracking-tight uppercase">Login ⚡</h3>
                <p class="text-slate-500 font-medium">Acesse sua central de inteligência.</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form wire:submit="login" class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">E-mail de Acesso</label>
                    <input wire:model="form.email" type="email" required autofocus class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:border-indigo-500 focus:ring-0 focus:bg-white/10 transition-all placeholder:text-slate-700 shadow-inner" placeholder="seu@email.com.br" />
                    <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-rose-500 text-xs font-bold" />
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center ml-1">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Senha</label>
                        @if (Route::has('password.request'))
                            <a class="text-[10px] font-black text-indigo-500 hover:text-indigo-400 uppercase tracking-widest transition-colors" href="{{ route('password.request') }}" wire:navigate>Esqueceu?</a>
                        @endif
                    </div>
                    <input wire:model="form.password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white focus:border-indigo-500 focus:ring-0 focus:bg-white/10 transition-all placeholder:text-slate-700 shadow-inner" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-rose-500 text-xs font-bold" />
                </div>

                <div class="flex items-center gap-3 ml-1">
                    <input wire:model="form.remember" id="remember" type="checkbox" class="w-5 h-5 rounded-lg bg-white/5 border-white/10 text-indigo-600 focus:ring-offset-dark-950 focus:ring-indigo-500 cursor-pointer">
                    <label for="remember" class="text-xs font-bold text-slate-500 uppercase tracking-tight cursor-pointer select-none">Manter conectado</label>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-2xl shadow-2xl shadow-indigo-600/30 transition-all transform hover:-translate-y-1 active:scale-95 uppercase tracking-[0.2em] text-xs flex items-center justify-center gap-3 group">
                        Acessar Dashboard <i class="fas fa-chevron-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
