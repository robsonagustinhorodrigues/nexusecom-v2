<?php

use function Livewire\Volt\{state, computed, rules, usesPagination};
use App\Models\Parceiro;
use Illuminate\Support\Facades\Auth;

usesPagination();

state([
    'search' => '',
    'tipo' => '',
    'editing' => null,
    'nome' => '',
    'cpf_cnpj' => '',
    'tipo_form' => 'cliente',
    'email' => '',
    'telefone' => '',
    'endereco' => '',
    'showModal' => false,
]);

rules([
    'nome' => 'required|min:3',
    'cpf_cnpj' => 'nullable',
    'tipo_form' => 'required|in:cliente,fornecedor,ambos',
    'email' => 'nullable|email',
]);

$parceiros = computed(function () {
    $empresaId = Auth::user()->current_empresa_id;
    
    if (!$empresaId) {
        return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
    }

    return Parceiro::tenant($empresaId)
        ->when($this->search, fn($q) => $q->where('nome', 'like', "%{$this->search}%")
            ->orWhere('cpf_cnpj', 'like', "%{$this->search}%"))
        ->when($this->tipo, fn($q) => $q->where('tipo', $this->tipo))
        ->orderBy('nome')
        ->paginate(10);
});

$save = function () {
    $this->validate();

    $data = [
        'empresa_id' => Auth::user()->current_empresa_id,
        'nome' => $this->nome,
        'cpf_cnpj' => $this->cpf_cnpj,
        'tipo' => $this->tipo_form,
        'email' => $this->email,
        'telefone' => $this->telefone,
        'endereco' => $this->endereco,
    ];

    if ($this->editing) {
        $parceiro = Parceiro::findOrFail($this->editing);
        $parceiro->update($data);
    } else {
        Parceiro::create($data);
    }

    $this->resetForm();
    $this->showModal = false;
};

$edit = function ($id) {
    $parceiro = Parceiro::findOrFail($id);
    $this->editing = $parceiro->id;
    $this->nome = $parceiro->nome;
    $this->cpf_cnpj = $parceiro->cpf_cnpj;
    $this->tipo_form = $parceiro->tipo;
    $this->email = $parceiro->email;
    $this->telefone = $parceiro->telefone;
    $this->endereco = $parceiro->endereco;
    $this->showModal = true;
};

$delete = function ($id) {
    Parceiro::findOrFail($id)->delete();
};

$resetForm = function () {
    $this->reset(['editing', 'nome', 'cpf_cnpj', 'tipo_form', 'email', 'telefone', 'endereco']);
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                <i class="fas fa-handshake text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Parceiros Unificados</h2>
                <p class="text-sm text-slate-500">Gestão de clientes e fornecedores</p>
            </div>
        </div>

        <button wire:click="$set('showModal', true)" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
            <i class="fas fa-plus text-xs"></i>
            Novo Parceiro
        </button>
    </div>

    <!-- Alertas -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('message') }}
        </div>
    @endif

    <!-- Filtros -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            <div class="relative">
                <input wire:model.live="search" type="text" placeholder="Buscar por Nome ou CPF/CNPJ..." class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 transition-all pl-10">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>
        </div>
        <div>
            <select wire:model.live="tipo" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 transition-all h-[50px]">
                <option value="">Todos os Tipos</option>
                <option value="cliente">Clientes</option>
                <option value="fornecedor">Fornecedores</option>
                <option value="ambos">Ambos</option>
            </select>
        </div>
    </div>

    <!-- Tabela -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nome / Razão Social</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">CPF / CNPJ</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Contato</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                    @forelse($this->parceiros as $parceiro)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/20 to-teal-600/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold text-sm">
                                    {{ substr($parceiro->nome, 0, 2) }}
                                </div>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $parceiro->nome }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-mono text-slate-500">{{ $parceiro->cpf_cnpj ?? 'N/D' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($parceiro->tipo === 'cliente')
                                <span class="px-3 py-1 rounded-lg bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 text-xs font-semibold">Cliente</span>
                            @elseif($parceiro->tipo === 'fornecedor')
                                <span class="px-3 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">Fornecedor</span>
                            @else
                                <span class="px-3 py-1 rounded-lg bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 text-xs font-semibold">Ambos</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <p class="text-slate-600 dark:text-slate-300">{{ $parceiro->email }}</p>
                                <p class="text-xs text-slate-400">{{ $parceiro->telefone }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button wire:click="edit({{ $parceiro->id }})" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-800 text-slate-400 hover:text-indigo-600 dark:hover:text-white transition-all">
                                    <i class="fas fa-edit text-sm"></i>
                                </button>
                                <button wire:click="delete({{ $parceiro->id }})" wire:confirm="Tem certeza que deseja excluir?" class="p-2 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-500/20 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-all">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-handshake text-4xl text-slate-300 dark:text-slate-600 mb-3"></i>
                                <p class="text-slate-500 font-medium">Nenhum parceiro encontrado</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($this->parceiros->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-dark-800">
            {{ $this->parceiros->links() }}
        </div>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-dark-950/80 backdrop-blur-sm">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl w-full max-w-2xl shadow-xl overflow-hidden" @click.away="$wire.showModal = false">
            <header class="p-6 border-b border-slate-200 dark:border-dark-800 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas {{ $editing ? 'fa-edit' : 'fa-plus' }} text-indigo-500"></i>
                    {{ $editing ? 'Editar Parceiro' : 'Novo Parceiro' }}
                </h3>
                <button wire:click="$set('showModal', false); resetForm();" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </header>

            <form wire:submit.prevent="save" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nome / Razão Social</label>
                        <input wire:model="nome" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Nome do parceiro">
                        @error('nome') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">CPF / CNPJ</label>
                        <input wire:model="cpf_cnpj" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-mono focus:border-indigo-500" placeholder="00.000.000/0000-00">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tipo de Entidade</label>
                        <select wire:model="tipo_form" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 h-[50px]">
                            <option value="cliente">Cliente</option>
                            <option value="fornecedor">Fornecedor</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">E-mail</label>
                        <input wire:model="email" type="email" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="email@exemplo.com">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Telefone</label>
                        <input wire:model="telefone" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="(00) 00000-0000">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Endereço Completo</label>
                        <textarea wire:model="endereco" rows="2" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Endereço completo"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-slate-200 dark:border-dark-800">
                    <button type="button" wire:click="$set('showModal', false); resetForm();" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-dark-700 text-slate-600 dark:text-slate-400 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-dark-800 transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
                        <i class="fas fa-save text-xs"></i>
                        {{ $editing ? 'Salvar Alterações' : 'Cadastrar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
