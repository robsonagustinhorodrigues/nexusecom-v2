@extends('layouts.alpine')

@section('title', 'Tarefas - NexusEcom')
@section('header_title', 'Tarefas')

@section('content')
<div x-data="tarefasPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Monitor de Tarefas</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Acompanhe o progresso das tarefas em background</p>
        </div>
        <button @click="limparConcluidas()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-xl text-xs font-bold italic text-white flex items-center gap-2">
            <i class="fas fa-broom"></i>
            Limpar concluídas
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center">
                    <i class="fas fa-list text-slate-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-white" x-text="stats.total">0</p>
                    <p class="text-xs text-slate-500 font-bold uppercase">Total</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                    <i class="fas fa-spinner text-indigo-400 fa-spin"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-indigo-400" x-text="stats.processando">0</p>
                    <p class="text-xs text-slate-500 font-bold uppercase">Em andamento</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <i class="fas fa-check text-emerald-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-emerald-400" x-text="stats.concluido">0</p>
                    <p class="text-xs text-slate-500 font-bold uppercase">Concluídas</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-rose-500/20 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-rose-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-rose-400" x-text="stats.erro">0</p>
                    <p class="text-xs text-slate-500 font-bold uppercase">Erros</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Tarefas -->
    <div class="bg-slate-800 border border-slate-700 rounded-2xl overflow-hidden">
        <div x-show="loading" class="p-12 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
        </div>

        <div x-show="!loading && tasks.length === 0" class="p-12 text-center text-slate-500">
            <i class="fas fa-tasks text-4xl mb-4 opacity-50"></i>
            <p class="font-bold">Nenhuma tarefa em andamento</p>
        </div>

        <div class="divide-y divide-slate-700/50">
            <template x-for="task in tasks" :key="task.id">
                <div class="p-4 hover:bg-slate-700/30 transition-colors">
                    <div class="flex items-center gap-4">
                        <!-- Status Icon -->
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="getStatusClass(task.status)">
                            <i :class="getStatusIcon(task.status)"></i>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white text-sm" x-text="task.tipo || 'Tarefa'"></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase" 
                                      :class="getStatusBadge(task.status)" x-text="task.status"></span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1" x-text="task.descricao || '-'"></p>
                            <div class="flex items-center gap-4 mt-2 text-[10px] text-slate-600">
                                <span x-text="task.empresa?.nome || 'Empresa'"></span>
                                <span x-text="formatDate(task.created_at)"></span>
                            </div>
                        </div>

                        <!-- Progress -->
                        <div class="w-32 flex-shrink-0" x-show="task.status === 'processando'">
                            <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full animate-pulse" style="width: 60%"></div>
                            </div>
                        </div>

                        <!-- Error Details -->
                        <div x-show="task.status === 'erro'" class="flex-shrink-0">
                            <button @click="showError(task)" class="text-rose-400 hover:text-rose-300 text-xs">
                                <i class="fas fa-exclamation-circle"></i> Ver erro
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Error Modal -->
    <div x-show="errorModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80" @click="errorModal = false">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-6 max-w-lg w-full" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-white">Detalhes do Erro</h3>
                <button @click="errorModal = false" class="text-slate-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <pre x-text="errorDetails" class="bg-slate-900 p-4 rounded-lg text-xs text-rose-400 overflow-auto max-h-64"></pre>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function tarefasPage() {
    return {
        tasks: [],
        stats: { total: 0, processando: 0, concluido: 0, erro: 0 },
        loading: true,
        errorModal: false,
        errorDetails: '',

        async init() {
            await this.loadTasks();
            // Auto-refresh every 10 seconds
            setInterval(() => this.loadTasks(), 10000);
        },

        async loadTasks() {
            this.loading = true;
            try {
                const response = await fetch('/api/admin/tarefas');
                if (response.ok) {
                    const data = await response.json();
                    this.tasks = data.tarefas || [];
                    this.stats = data.stats || { total: 0, processando: 0, concluido: 0, erro: 0 };
                }
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        async limparConcluidas() {
            if (!confirm('Limpar tarefas concluídas?')) return;
            try {
                await fetch('/api/admin/tarefas/limpar', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                await this.loadTasks();
            } catch (e) { console.error(e); }
        },

        showError(task) {
            this.errorDetails = task.erro || 'Sem detalhes';
            this.errorModal = true;
        },

        getStatusClass(status) {
            if (status === 'processando') return 'bg-indigo-500/20 text-indigo-400';
            if (status === 'concluido') return 'bg-emerald-500/20 text-emerald-400';
            if (status === 'erro') return 'bg-rose-500/20 text-rose-400';
            return 'bg-slate-700 text-slate-400';
        },

        getStatusIcon(status) {
            if (status === 'processando') return 'fas fa-spinner fa-spin';
            if (status === 'concluido') return 'fas fa-check';
            if (status === 'erro') return 'fas fa-exclamation-triangle';
            return 'fas fa-clock';
        },

        getStatusBadge(status) {
            if (status === 'processando') return 'bg-indigo-500/20 text-indigo-400';
            if (status === 'concluido') return 'bg-emerald-500/20 text-emerald-400';
            if (status === 'erro') return 'bg-rose-500/20 text-rose-400';
            return 'bg-slate-700 text-slate-400';
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleString('pt-BR');
        }
    }
}
</script>
@endsection
