<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" 
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-dark-800 border border-slate-200 dark:border-dark-700 hover:border-indigo-500 transition-all shadow-sm">
        @if($currentEmpresa && $currentEmpresa->logo_path)
            <img src="{{ Storage::url($currentEmpresa->logo_path) }}" class="w-5 h-5 rounded object-cover">
        @else
            <i class="fas fa-building text-indigo-600 dark:text-indigo-400"></i>
        @endif
        <span class="text-sm font-bold text-slate-700 dark:text-slate-200">
            {{ $currentEmpresa->nome ?? 'Selecionar Empresa' }}
        </span>
        <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
    </button>
    
    <div x-show="open" 
         @click.away="open = false" 
         x-cloak 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute left-0 mt-2 w-56 rounded-xl bg-white dark:bg-dark-800 border border-slate-200 dark:border-dark-700 shadow-2xl z-[101] overflow-hidden">
         
        @forelse($empresas as $empresa)
            <button wire:click="selectCompany({{ $empresa->id }})" 
               class="w-full text-left block px-4 py-3 text-sm transition-colors {{ ($currentEmpresa && $currentEmpresa->id == $empresa->id) ? 'text-indigo-600 dark:text-white bg-indigo-50 dark:bg-indigo-600 font-bold border-b border-slate-100 dark:border-dark-700/50' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-dark-700' }}">
                <div class="flex items-center gap-2">
                    @if($empresa->logo_path)
                        <img src="{{ Storage::url($empresa->logo_path) }}" class="w-5 h-5 rounded object-cover">
                    @else
                        <i class="fas fa-building text-xs"></i>
                    @endif
                    {{ $empresa->nome }}
                </div>
            </button>
        @empty
            <div class="px-4 py-6 text-center">
                <i class="fas fa-building text-slate-300 dark:text-dark-600 text-2xl mb-2 block"></i>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest italic leading-tight">Nenhuma empresa<br>vinculada ao grupo</p>
            </div>
        @endforelse
    </div>
</div>
