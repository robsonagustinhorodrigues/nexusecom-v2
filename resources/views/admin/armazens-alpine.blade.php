@extends('layouts.alpine')

@section('title', 'Depósitos - NexusEcom')
@section('header_title', 'Depósitos')

@section('content')
<div x-data="depositosPage()" x-init="init()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight italic uppercase">Hub de Depósitos</h2>
            <p class="text-sm text-slate-400 italic underline decoration-indigo-500/50 decoration-2 underline-offset-4">Gerenciamento de Armazenagem & Logística</p>
        </div>
        <button @click="openCreate()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl font-bold transition-all flex items-center gap-2">
            <i class="fas fa-plus"></i> Novo Depósito
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <template x-for="deposito in depositos" :key="deposito.id">
            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5 hover:border-indigo-500/50 transition-all group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-500">
                        <i class="fas fa-warehouse text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white" x-text="deposito.nome"></h3>
                        <span class="text-[10px] uppercase font-bold text-slate-500" x-text="deposito.tipo"></span>
                    </div>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Empresa</span>
                        <span class="text-slate-300" x-text="deposito.empresa?.nome || (deposito.compartilhado ? 'Compartilhado' : '-')"></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-500">Status</span>
                        <span :class="deposito.ativo ? 'text-emerald-400' : 'text-rose-400'" class="font-bold" x-text="deposito.ativo ? 'Ativo' : 'Inativo'"></span>
                    </div>
                    <template x-if="deposito.compartilhado_com && deposito.compartilhado_com.length">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500">Compartilhado com</span>
                            <span class="text-slate-300 text-right" x-text="deposito.compartilhado_com.map(e => e.nome).join(', ')"></span>
                        </div>
                    </template>
                </div>

                <div class="flex gap-2 pt-4 border-t border-slate-700">
                    <button @click="editDeposito(deposito)" class="flex-1 py-2 bg-slate-700 hover:bg-indigo-600 text-xs font-bold rounded-lg transition-all">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </button>
                    <button @click="deleteDeposito(deposito.id)" class="px-3 py-2 bg-slate-700 hover:bg-red-600 text-xs font-bold rounded-lg transition-all text-rose-400 hover:text-white">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty -->
    <div x-show="!loading && depositos.length === 0" class="text-center py-12">
        <i class="fas fa-warehouse text-4xl text-slate-600 mb-4"></i>
        <p class="text-slate-400">Nenhum depósito encontrado</p>
    </div>

    <!-- Modal Form -->
    <div x-show="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
        <div class="bg-slate-800 border border-slate-700 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden" @click.away="showModal = false">
            <div class="p-6 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white" x-text="isEditing ? 'Editar Depósito' : 'Novo Depósito'"></h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nome</label>
                    <input type="text" x-model="form.nome" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tipo</label>
                    <select x-model="form.tipo" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white focus:border-indigo-500 outline-none">
                        <option value="armazem">Armazém Físico</option>
                        <option value="loja">Loja / PDV</option>
                        <option value="full">Fulfillment</option>
                        <option value="virtual">Virtual / Drop</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">
                        <input type="checkbox" x-model="form.compartilhado" class="mr-2"> Compartilhar com empresas
                    </label>
                </div>
                <div x-show="form.compartilhado">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Selecione as empresas</label>
                    <div class="max-h-32 overflow-y-auto bg-slate-900 border border-slate-700 rounded-xl p-2">
                        <template x-for="emp in empresas" :key="emp.id">
                            <label class="flex items-center gap-2 p-2 hover:bg-slate-800 rounded cursor-pointer">
                                <input type="checkbox" :value="emp.id" x-model="form.compartilhado_com" class="rounded bg-slate-700 border-slate-600 text-indigo-500">
                                <span class="text-white text-sm" x-text="emp.nome"></span>
                            </label>
                        </template>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Selecione as empresas que podem usar este depósito</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Status</label>
                    <select x-model="form.ativo" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-white focus:border-indigo-500 outline-none">
                        <option :value="true">Ativo</option>
                        <option :value="false">Inativo</option>
                    </select>
                </div>
                <div class="mt-8 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-6 py-2.5 text-slate-400 font-bold">Cancelar</button>
                    <button @click="save()" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-2.5 rounded-xl font-bold transition-all">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function depositosPage() {
    return {
        depositos: [],
        empresas: [],
        loading: false,
        showModal: false,
        isEditing: false,
        form: { id: null, nome: '', tipo: 'armazem', compartilhado: false, compartilhado_com: [], ativo: true },
        
        async init() {
            await this.loadEmpresas();
            await this.loadDepositos();
        },
        
        async loadEmpresas() {
            try {
                const response = await fetch('/api/admin/empresas');
                this.empresas = await response.json();
            } catch (e) { console.error(e); }
        },
        
        async loadDepositos() {
            this.loading = true;
            try {
                const response = await fetch('/api/estoque/depositos');
                this.depositos = await response.json();
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        
        openCreate() {
            this.isEditing = false;
            this.form = { id: null, nome: '', tipo: 'armazem', compartilhado: false, compartilhado_com: [], ativo: true };
            this.showModal = true;
        },
        
        editDeposito(item) {
            this.isEditing = true;
            this.form = { 
                id: item.id, 
                nome: item.nome, 
                tipo: item.tipo,
                compartilhado: item.compartilhado,
                compartilhado_com: item.compartilhado_com ? item.compartilhado_com.map(e => e.id) : [],
                ativo: item.ativo
            };
            this.showModal = true;
        },
        
        async save() {
            const method = this.isEditing ? 'PUT' : 'POST';
            const url = this.isEditing ? `/api/estoque/depositos/${this.form.id}` : '/api/estoque/depositos';
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });
                
                if (response.ok) {
                    this.showModal = false;
                    await this.loadDepositos();
                } else {
                    const error = await response.json();
                    alert('Erro ao salvar: ' + (error.message || error.error || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error('Erro:', e);
                alert('Erro na conexão com o servidor: ' + e.message);
            }
        },

        async deleteDeposito(id) {
            if(!confirm('Deseja excluir este depósito?')) return;
            
            try {
                const response = await fetch(`/api/estoque/depositos/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                if (response.ok) {
                    await this.loadDepositos();
                }
            } catch (e) { console.error(e); }
        }
    }
}
</script>
@endsection
