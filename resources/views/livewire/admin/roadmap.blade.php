<?php

use App\Livewire\Admin\Roadmap;

?>

<div class="space-y-8">
    <div class="text-center py-8">
        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase">
            ⚡ NexusEcom
        </h1>
        <p class="text-slate-500 font-medium italic mt-2">Implementação</p>
    </div>

    @foreach($phases as $phase)
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="p-6 border-b border-slate-100 dark:border-dark-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center 
                            {{ $phase['color'] === 'green' ? 'bg-emerald-500/10 text-emerald-400' : '' }}
                            {{ $phase['color'] === 'indigo' ? 'bg-indigo-500/10 text-indigo-400' : '' }}
                            {{ $phase['color'] === 'amber' ? 'bg-amber-500/10 text-amber-400' : '' }}
                            {{ $phase['color'] === 'rose' ? 'bg-rose-500/10 text-rose-400' : '' }}
                            {{ $phase['color'] === 'emerald' ? 'bg-emerald-500/10 text-emerald-400' : '' }}
                        ">
                            <span class="text-xl font-black">{{ $phase['id'] }}</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase italic tracking-tight">
                                {{ $phase['title'] }}
                            </h3>
                            <span class="text-xs font-bold uppercase tracking-widest 
                                {{ $phase['status'] === 'Concluído' ? 'text-emerald-400' : '' }}
                                {{ $phase['status'] === 'Em Andamento' ? 'text-indigo-400' : '' }}
                                {{ $phase['status'] === 'Iniciado' ? 'text-amber-400' : '' }}
                                {{ $phase['status'] === 'Pendente' ? 'text-slate-400' : '' }}
                            ">
                                {{ $phase['status'] }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-3xl font-black 
                            {{ $phase['color'] === 'green' ? 'text-emerald-400' : '' }}
                            {{ $phase['color'] === 'indigo' ? 'text-indigo-400' : '' }}
                            {{ $phase['color'] === 'amber' ? 'text-amber-400' : '' }}
                            {{ $phase['color'] === 'rose' ? 'text-rose-400' : '' }}
                            {{ $phase['color'] === 'emerald' ? 'text-emerald-400' : '' }}
                        ">
                            {{ $phase['progress'] }}%
                        </span>
                    </div>
                </div>
                <div class="mt-4 h-2 bg-slate-100 dark:bg-dark-800 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 @if($phase['color'] === 'green') bg-emerald-400 @elseif($phase['color'] === 'indigo') bg-indigo-400 @elseif($phase['color'] === 'amber') bg-amber-400 @elseif($phase['color'] === 'rose') bg-rose-400 @else bg-emerald-400 @endif" style="width: {{ $phase['progress'] }}%"></div>
                </div>
            </div>
            
            <div class="p-6">
                <p class="text-slate-600 dark:text-slate-400 italic mb-6">
                    {{ $phase['description'] }}
                </p>
                
                @if(!empty($phase['technical_details']))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        @foreach($phase['technical_details'] as $key => $detail)
                            <div class="bg-slate-50 dark:bg-dark-950 p-4 rounded-2xl">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">
                                    {{ $key }}
                                </span>
                                <span class="text-sm text-slate-700 dark:text-slate-300 italic">
                                    {{ $detail }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                @if(!empty($phase['tasks']))
                    <div class="flex flex-wrap gap-2">
                        @foreach($phase['tasks'] as $taskName => $status)
                            @if(is_string($status))
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest 
                                    {{ $status === 'OK' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                                    {{ $taskName }}: {{ $status }}
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-slate-100 dark:bg-dark-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-dark-700">
                                    {{ $task }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endforeach
    
    <footer class="p-8 bg-dark-900 border border-dark-800 rounded-3xl text-center shadow-inner">
        <p class="text-slate-500 text-sm font-medium italic">
            "NexusEcom ⚡: Replicando o sucesso do UpSeller com a inteligência do futuro."
        </p>
    </footer>
</div>
