@extends('layouts.alpine')

@section('title', 'Equipe - NexusEcom')
@section('header_title', 'Equipe')

@section('content')
<div x-data="usuariosPage()" x-init="init()">
    <!-- Header Actions -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fas fa-users text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Gestão de Equipe</h2>
                <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Controle de acessos e permissões do sistema</p>
                <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Controle de Acessos & Permissões</p>
            </div>
        </div>

        <button @click="openCreate()" class="px-6 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2 italic uppercase tracking-wider group">
            <i class="fas fa-user-plus text-xs group-hover:scale-110 transition-transform"></i>
            Adicionar Membro
        </button>
    </div>

    <!-- Tabela de Usuários -->
    <div class="bg-slate-800 border border-slate-700 rounded-3xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900/50 border-b border-slate-700">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Usuário</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Nível de Acesso</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Empresas</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <template x-for="user in users" :key="user.id">
                        <tr class="hover:bg-slate-700/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-black text-sm italic" x-text="user.name.substring(0,2).toUpperCase()"></div>
                                    <div>
                                        <p class="text-sm font-black text-white italic uppercase tracking-tight" x-text="user.name"></p>
                                        <p class="text-[10px] text-slate-500 font-bold" x-text="user.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="role in user.roles" :key="role.id">
                                        <span class="px-2 py-0.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[10px] font-black uppercase italic" x-text="role.name"></span>
                                    </template>
                                    <template x-if="user.roles.length === 0">
                                        <span class="text-[10px] text-slate-600 font-bold italic uppercase">Sem Função</span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="empresa in user.empresas" :key="empresa.id">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-900/50 border border-slate-700 text-slate-400 text-[10px] font-black uppercase italic" x-text="empresa.nome"></span>
                                    </template>
                                    <template x-if="user.empresas.length === 0">
                                        <span class="text-[10px] text-slate-600 font-bold italic uppercase">Nenhuma</span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="editUser(user)" class="p-2 text-slate-500 hover:text-indigo-400 hover:bg-slate-700 rounded-xl transition-all">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <div x-show="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak x-transition>
        <div class="bg-slate-800 border border-slate-700 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden" @click.away="showModal = false">
            <div class="p-6 border-b border-slate-700 flex justify-between items-center bg-slate-900/50">
                <h3 class="text-lg font-black text-white italic uppercase tracking-tight" x-text="isEditing ? 'Editar Membro' : 'Novo Membro'"></h3>
                <button @click="showModal = false" class="text-slate-500 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-4 italic">Identidade & Acesso</h4>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Nome Completo</label>
                            <input type="text" x-model="form.name" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-4 py-3 text-white font-bold focus:border-indigo-500 outline-none transition-all italic">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">E-mail</label>
                            <input type="email" x-model="form.email" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-4 py-3 text-white font-bold focus:border-indigo-500 outline-none transition-all italic">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2" x-text="isEditing ? 'Alterar Senha' : 'Senha'"></label>
                            <input type="password" x-model="form.password" placeholder="••••••••" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-4 py-3 text-white font-bold focus:border-indigo-500 outline-none transition-all italic">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-4 italic">Nível & Escopo</h4>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Função (Role)</label>
                            <select x-model="form.role_id" class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-4 py-3 text-white font-bold focus:border-indigo-500 outline-none transition-all italic">
                                <option value="">Selecione...</option>
                                <template x-for="role in roles" :key="role.id">
                                    <option :value="role.id" x-text="role.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 italic">Acesso às Empresas</label>
                            <div class="grid grid-cols-1 gap-2 bg-slate-900/50 p-4 rounded-2xl border border-slate-700 max-h-40 overflow-y-auto custom-scrollbar">
                                <template x-for="empresa in empresas" :key="empresa.id">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" :value="empresa.id" x-model="form.selected_empresas" class="w-4 h-4 rounded border-slate-600 bg-slate-800 text-indigo-600 focus:ring-0">
                                        <span class="text-xs font-bold text-slate-400 group-hover:text-white transition-colors italic uppercase" x-text="empresa.nome"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-end gap-4 border-t border-slate-700 pt-6">
                    <button @click="showModal = false" class="px-6 py-2.5 text-slate-500 font-black italic uppercase text-xs tracking-widest hover:text-white transition-colors">Cancelar</button>
                    <button @click="save()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-10 py-3 rounded-2xl font-black shadow-lg shadow-indigo-600/20 transition-all italic uppercase text-sm tracking-tighter">
                        Confirmar Membro
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function usuariosPage() {
    return {
        users: [],
        roles: [],
        empresas: [],
        loading: false,
        showModal: false,
        isEditing: false,
        form: { id: null, name: '', email: '', password: '', role_id: '', selected_empresas: [] },
        
        async init() {
            await Promise.all([this.loadUsers(), this.loadRoles(), this.loadEmpresas()]);
        },
        
        async loadUsers() {
            this.loading = true;
            try {
                const response = await fetch('/api/admin/usuarios');
                this.users = await response.json();
            } catch (e) { console.error(e); }
            this.loading = false;
        },

        async loadRoles() {
            try {
                const response = await fetch('/api/admin/roles');
                this.roles = await response.json();
            } catch (e) { console.error(e); }
        },

        async loadEmpresas() {
            try {
                const response = await fetch('/api/admin/empresas');
                this.empresas = await response.json();
            } catch (e) { console.error(e); }
        },
        
        openCreate() {
            this.isEditing = false;
            this.form = { id: null, name: '', email: '', password: '', role_id: '', selected_empresas: [] };
            this.showModal = true;
        },
        
        editUser(user) {
            this.isEditing = true;
            this.form = { 
                id: user.id, 
                name: user.name, 
                email: user.email, 
                password: '', 
                role_id: user.roles[0]?.id || '',
                selected_empresas: user.empresas.map(e => e.id)
            };
            this.showModal = true;
        },
        
        async save() {
            const method = this.isEditing ? 'PUT' : 'POST';
            const url = this.isEditing ? `/api/admin/usuarios/${this.form.id}` : '/api/admin/usuarios';
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(this.form)
                });
                if (response.ok) {
                    this.showModal = false;
                    await this.loadUsers();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao salvar');
                }
            } catch (e) { console.error(e); }
        }
    }
}
</script>
@endsection
