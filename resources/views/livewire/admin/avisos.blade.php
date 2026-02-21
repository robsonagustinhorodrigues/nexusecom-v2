<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center shadow-lg shadow-rose-500/20">
                <i class="fas fa-bell text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Avisos e Notificações</h2>
                <p class="text-sm text-slate-500">Histórico de alertas e atividades do sistema</p>
            </div>
        </div>

        <div class="flex gap-2">
            <button wire:click="marcarTodasLida" class="px-4 py-2 rounded-xl bg-white dark:bg-dark-800 border border-slate-200 dark:border-dark-700 text-slate-600 dark:text-slate-400 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-dark-700 transition-all flex items-center gap-2">
                <i class="fas fa-check-double text-xs"></i>
                Marcar tudo como lido
            </button>
            <button wire:click="limparTodas" wire:confirm="Tem certeza que deseja excluir todos os avisos?" class="px-4 py-2 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 font-semibold text-sm hover:bg-rose-100 dark:hover:bg-rose-500/20 transition-all flex items-center gap-2">
                <i class="fas fa-trash-alt text-xs"></i>
                Limpar tudo
            </button>
        </div>
    </div>

    <!-- Lista de Notificações -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        @forelse($notificacoes as $notificacao)
        <div class="p-4 border-b border-slate-100 dark:border-dark-800 hover:bg-slate-50 dark:hover:bg-dark-800/50 transition-colors {{ $notificacao->read ? 'opacity-60' : '' }}">
            <div class="flex items-start gap-4">
                <!-- Ícone -->
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                    @if($notificacao->type === 'success') bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400
                    @elseif($notificacao->type === 'warning') bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400
                    @elseif($notificacao->type === 'error') bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400
                    @else bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400
                    @endif
                ">
                    @if($notificacao->type === 'success')
                        <i class="fas fa-check"></i>
                    @elseif($notificacao->type === 'warning')
                        <i class="fas fa-exclamation"></i>
                    @elseif($notificacao->type === 'error')
                        <i class="fas fa-times"></i>
                    @else
                        <i class="fas fa-info"></i>
                    @endif
                </div>

                <!-- Conteúdo -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h4 class="font-semibold text-slate-900 dark:text-white truncate">
                            {{ $notificacao->title }}
                        </h4>
                        @if(!$notificacao->read)
                        <span class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $notificacao->message }}</p>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-xs text-slate-400">
                            {{ $notificacao->created_at->diffForHumans() }}
                        </span>
                        @if($notificacao->link)
                        <a href="{{ $notificacao->link }}" class="text-xs font-semibold text-indigo-500 hover:text-indigo-400">
                            Ver detalhes <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                        @endif
                        @if(isset($notificacao->data['log_path']) && isset($notificacao->data['log_filename']))
                        <a href="{{ route('nfe.download-log', ['path' => $notificacao->data['log_path'], 'filename' => $notificacao->data['log_filename']]) }}" class="text-xs font-semibold text-amber-500 hover:text-amber-400 flex items-center gap-1">
                            <i class="fas fa-download"></i>
                            Baixar Log
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Ações -->
                <div class="flex items-center gap-1">
                    @if(!$notificacao->read)
                    <button wire:click="marcarLida({{ $notificacao->id }})" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-700 text-slate-400 hover:text-indigo-500 transition-all" title="Marcar como lido">
                        <i class="fas fa-check text-sm"></i>
                    </button>
                    @endif
                    <button wire:click="excluir({{ $notificacao->id }})" class="p-2 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-500/20 text-slate-400 hover:text-rose-500 transition-all" title="Excluir">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-dark-800 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bell-slash text-2xl text-slate-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">Nenhum aviso</h3>
            <p class="text-sm text-slate-500">Você não tem notificações no momento</p>
        </div>
        @endforelse

        @if($notificacoes->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-dark-800">
            {{ $notificacoes->links() }}
        </div>
        @endif
    </div>
</div>
