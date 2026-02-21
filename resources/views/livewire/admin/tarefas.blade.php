<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20">
                <i class="fas fa-tasks text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Monitor de Tarefas</h2>
                <p class="text-sm text-slate-500">Acompanhe o progresso das tarefas em background</p>
            </div>
        </div>

        <button wire:click="limparConcluidas" wire:confirm="Limpar tarefas concluídas?" class="px-4 py-2 rounded-xl bg-white dark:bg-dark-800 border border-slate-200 dark:border-dark-700 text-slate-600 dark:text-slate-400 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-dark-700 transition-all flex items-center gap-2">
            <i class="fas fa-broom text-xs"></i>
            Limpar concluídas
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-dark-800 flex items-center justify-center">
                    <i class="fas fa-list text-slate-500"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
                    <p class="text-xs text-slate-500">Total</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center">
                    <i class="fas fa-spinner text-indigo-500"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['processando'] }}</p>
                    <p class="text-xs text-slate-500">Em andamento</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center">
                    <i class="fas fa-check text-emerald-500"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['concluido'] }}</p>
                    <p class="text-xs text-slate-500">Concluídas</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-rose-100 dark:bg-rose-500/20 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-rose-500"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400">{{ $stats['falhou'] }}</p>
                    <p class="text-xs text-slate-500">Falhas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtro -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
        <select wire:model.live="filtroStatus" class="w-full md:w-auto bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-indigo-500">
            <option value="">Todos os status</option>
            <option value="pending">Pendente</option>
            <option value="processando">Em andamento</option>
            <option value="concluido">Concluído</option>
            <option value="concluido_com_erros">Concluído com erros</option>
            <option value="falhou">Falhou</option>
            <option value="cancelado">Cancelado</option>
        </select>
    </div>

    <!-- Lista de Tarefas -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        @forelse($tarefas as $tarefa)
        <div class="p-4 border-b border-slate-100 dark:border-dark-800">
            <div class="flex items-start gap-4">
                <!-- Status Icon -->
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                    @if($tarefa->status === 'processando') bg-indigo-100 dark:bg-indigo-500/20 text-indigo-500
                    @elseif($tarefa->status === 'concluido') bg-emerald-100 dark:bg-emerald-500/20 text-emerald-500
                    @elseif($tarefa->status === 'cancelado') bg-slate-200 dark:bg-dark-700 text-slate-500
                    @elseif(in_array($tarefa->status, ['falhou', 'concluido_com_erros'])) bg-rose-100 dark:bg-rose-500/20 text-rose-500
                    @else bg-slate-100 dark:bg-dark-800 text-slate-400
                    @endif
                ">
                    @if($tarefa->status === 'processando')
                        <i class="fas fa-spinner fa-spin"></i>
                    @elseif($tarefa->status === 'concluido')
                        <i class="fas fa-check"></i>
                    @elseif($tarefa->status === 'cancelado')
                        <i class="fas fa-ban"></i>
                    @elseif(in_array($tarefa->status, ['falhou', 'concluido_com_erros']))
                        <i class="fas fa-exclamation"></i>
                    @else
                        <i class="fas fa-clock"></i>
                    @endif
                </div>

                    <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-semibold text-slate-900 dark:text-white">
                                @if($tarefa->tipo === 'importacao_nfe')
                                    Importação de NF-e
                                @elseif($tarefa->tipo === 'sync_meli_pedidos')
                                    Sync Pedidos Mercado Livre
                                @elseif($tarefa->tipo === 'import_nfe_mercadolivre')
                                    Importar NFes Mercado Livre
                                @elseif($tarefa->tipo === 'import_nfe_bling')
                                    Importar NFes Bling
                                @elseif($tarefa->tipo === 'import_nfe_shopee')
                                    Importar NFes Shopee
                                @elseif($tarefa->tipo === 'import_nfe_amazon')
                                    Importar NFes Amazon
                                @elseif($tarefa->tipo === 'import_nfe_magalu')
                                    Importar NFes Magalu
                                @else
                                    {{ ucfirst($tarefa->tipo) }}
                                @endif
                            </h4>
                            @if($tarefa->empresa)
                                <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded text-xs font-medium">
                                    <i class="fas fa-building mr-1"></i>{{ $tarefa->empresa->nome }}
                                </span>
                            @elseif($tarefa->user)
                                <span class="px-2 py-0.5 bg-slate-100 dark:bg-dark-700 text-slate-500 rounded text-xs font-medium">
                                    <i class="fas fa-user mr-1"></i>{{ $tarefa->user->name }}
                                </span>
                            @endif
                        </div>
                        <span class="px-2 py-1 rounded-lg text-xs font-semibold
                            @if($tarefa->status === 'processando') bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400
                            @elseif($tarefa->status === 'concluido') bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400
                            @elseif($tarefa->status === 'cancelado') bg-slate-200 dark:bg-dark-700 text-slate-600 dark:text-slate-400
                            @elseif(in_array($tarefa->status, ['falhou', 'concluido_com_erros'])) bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400
                            @else bg-slate-100 dark:bg-dark-800 text-slate-500
                            @endif
                        ">
                            @if($tarefa->status === 'processando')
                                Processando
                            @elseif($tarefa->status === 'concluido')
                                Concluído
                            @elseif($tarefa->status === 'concluido_com_erros')
                                Com erros
                            @elseif($tarefa->status === 'falhou')
                                Falhou
                            @elseif($tarefa->status === 'cancelado')
                                Cancelado
                            @else
                                Pendente
                            @endif
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    @if($tarefa->status === 'processando' || $tarefa->estaConcluida())
                    <div class="mt-2">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>{{ $tarefa->processado }} / {{ $tarefa->total }}</span>
                            <span>{{ $tarefa->progress }}%</span>
                        </div>
                        <div class="h-2 bg-slate-100 dark:bg-dark-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 
                                @if($tarefa->status === 'processando') bg-indigo-500
                                @elseif($tarefa->status === 'concluido') bg-emerald-500
                                @else bg-rose-500
                                @endif
                            " style="width: {{ $tarefa->progress }}%"></div>
                        </div>
                    </div>
                    @endif

                    <!-- Stats -->
                    <div class="flex items-center gap-4 mt-2 text-xs">
                        @if($tarefa->sucesso > 0)
                        <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                            <i class="fas fa-check"></i> {{ $tarefa->sucesso }} OK
                        </span>
                        @endif
                        @if($tarefa->falha > 0)
                        <span class="flex items-center gap-1 text-rose-600 dark:text-rose-400">
                            <i class="fas fa-times"></i> {{ $tarefa->falha }} Falhas
                        </span>
                        @endif
                        <span class="text-slate-400">
                            <i class="fas fa-clock mr-1"></i>
                            {{ $tarefa->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <!-- Mensagem -->
                    @if($tarefa->mensagem)
                    <p class="text-xs text-slate-500 mt-2">{{ $tarefa->mensagem }}</p>
                    @endif

                    <!-- Ações -->
                    @if(in_array($tarefa->status, ['pending', 'processando']))
                    <div class="mt-3">
                        <button 
                            wire:click="cancelarTarefa({{ $tarefa->id }})"
                            wire:confirm="Cancelar esta tarefa?"
                            class="text-xs text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium"
                        >
                            <i class="fas fa-times mr-1"></i>
                            Cancelar
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-dark-800 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-tasks text-2xl text-slate-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Nenhuma tarefa</h3>
            <p class="text-sm text-slate-500">Não há tarefas em andamento ou concluídas</p>
        </div>
        @endforelse

        @if($tarefas->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-dark-800">
            {{ $tarefas->links() }}
        </div>
        @endif
    </div>
</div>
