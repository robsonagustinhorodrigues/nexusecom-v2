@extends('layouts.alpine')

@section('title', 'Avisos - NexusEcom')
@section('header_title', 'Avisos')

@section('content')
<div x-data="avisosPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Avisos & Notificações</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Histórico de alertas e atividades do sistema</p>
        </div>
        <div class="flex gap-2">
            <button @click="marcarTodasLida()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-xl text-xs font-bold italic text-white flex items-center gap-2">
                <i class="fas fa-check-double"></i>
                Marcar tudo como lido
            </button>
            <button @click="limparTodas()" class="px-4 py-2 bg-rose-600/20 hover:bg-rose-600/30 border border-rose-500/20 text-rose-400 rounded-xl text-xs font-bold italic flex items-center gap-2">
                <i class="fas fa-trash-alt"></i>
                Limpar tudo
            </button>
        </div>
    </div>

    <!-- Lista de Notificações -->
    <div class="bg-slate-800 border border-slate-700 rounded-2xl overflow-hidden">
        <div x-show="loading" class="p-12 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
        </div>
        
        <div x-show="!loading && notifications.length === 0" class="p-12 text-center text-slate-500">
            <i class="fas fa-bell-slash text-4xl mb-4 opacity-50"></i>
            <p class="font-bold">Nenhuma notificação</p>
        </div>

        <template x-for="notif in notifications" :key="notif.id">
            <div class="p-4 border-b border-slate-700/50 hover:bg-slate-700/30 transition-colors" :class="notif.read ? 'opacity-60' : ''">
                <div class="flex items-start gap-4">
                    <!-- Ícone -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         :class="getIconClass(notif.type)">
                        <i :class="getIcon(notif.type)"></i>
                    </div>

                    <!-- Conteúdo -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-bold text-white text-sm" x-text="notif.title"></p>
                                <p class="text-slate-400 text-xs mt-1" x-text="notif.message"></p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-[10px] text-slate-500" x-text="formatDate(notif.created_at)"></span>
                                <button @click="deleteNotif(notif.id)" class="text-slate-600 hover:text-rose-400 transition-colors">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@section('scripts')
<script>
function avisosPage() {
    return {
        notifications: [],
        loading: true,

        async init() {
            await this.loadNotifications();
        },

        async loadNotifications() {
            this.loading = true;
            try {
                const response = await fetch('/api/admin/notificacoes');
                if (response.ok) {
                    const data = await response.json();
                    this.notifications = data.notificacoes || [];
                }
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        async marcarTodasLida() {
            try {
                await fetch('/api/admin/notificacoes/marcar-lida', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                await this.loadNotifications();
            } catch (e) { console.error(e); }
        },

        async limparTodas() {
            if (!confirm('Tem certeza que deseja excluir todos os avisos?')) return;
            try {
                await fetch('/api/admin/notificacoes/limpar', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                this.notifications = [];
            } catch (e) { console.error(e); }
        },

        async deleteNotif(id) {
            try {
                await fetch(`/api/admin/notificacoes/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                this.notifications = this.notifications.filter(n => n.id !== id);
            } catch (e) { console.error(e); }
        },

        getIcon(type) {
            const icons = {
                'success': 'fas fa-check',
                'warning': 'fas fa-exclamation',
                'error': 'fas fa-times',
                'info': 'fas fa-info'
            };
            return icons[type] || icons['info'];
        },

        getIconClass(type) {
            const classes = {
                'success': 'bg-emerald-500/20 text-emerald-400',
                'warning': 'bg-amber-500/20 text-amber-400',
                'error': 'bg-rose-500/20 text-rose-400',
                'info': 'bg-indigo-500/20 text-indigo-400'
            };
            return classes[type] || classes['info'];
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleString('pt-BR');
        }
    }
}
</script>
@endsection
