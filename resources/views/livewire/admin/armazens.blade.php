<?php

use function Livewire\Volt\{state, computed, rules, usesPagination};
use App\Models\Armazem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

usesPagination();

state([
    'nome' => '',
    'endereco' => '',
    'compartilhado' => false,
    'ativo' => true,
    'editing' => null,
    'showModal' => false,
    'search' => '',
]);

rules([
    'nome' => 'required|min:3',
    'endereco' => 'nullable',
]);

$armazens = computed(function () {
    return Armazem::when($this->search, fn($q) => $q->where('nome', 'like', "%{$this->search}%"))
        ->orderBy('nome')
        ->paginate(10);
});

$save = function () {
    $this->validate();

    $data = [
        'nome' => $this->nome,
        'slug' => Str::slug($this->nome),
        'endereco' => $this->endereco,
        'compartilhado' => $this->compartilhado,
        'ativo' => $this->ativo,
    ];

    try {
        if ($this->editing) {
            $armazem = Armazem::findOrFail($this->editing);
            $armazem->update($data);
            session()->flash('message', 'Armazém atualizado com sucesso! ⚡');
        } else {
            if (Armazem::where('slug', $data['slug'])->exists()) {
                session()->flash('error', 'Já existe um armazém com este nome.');
                return;
            }

            $armazem = Armazem::create($data);
            session()->flash('message', 'Armazém criado com sucesso! ⚡');
        }

        $this->resetForm();
        $this->showModal = false;
    } catch (\Exception $e) {
        session()->flash('error', 'Ocorreu um erro ao salvar: ' . $e->getMessage());
    }
};

$edit = function ($id) {
    $armazem = Armazem::findOrFail($id);
    $this->editing = $armazem->id;
    $this->nome = $armazem->nome;
    $this->endereco = $armazem->endereco;
    $this->compartilhado = (bool)$armazem->compartilhado;
    $this->ativo = (bool)$armazem->ativo;
    $this->showModal = true;
};

$excluirArmazem = function ($id) {
    try {
        $armazem = Armazem::findOrFail($id);
        $armazem->delete();
        session()->flash('message', 'Armazém excluído com sucesso! 🗑️');
    } catch (\Exception $e) {
        session()->flash('error', 'Erro ao excluir armazém: ' . $e->getMessage());
    }
};

$resetForm = function () {
    $this->reset(['editing', 'nome', 'endereco', 'compartilhado', 'ativo']);
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/20">
                <i class="fas fa-warehouse text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Gestão de Armazéns WMS</h2>
                <p class="text-sm text-slate-500">Controle multiloque e gestão de estoque físico</p>
            </div>
        </div>

        <button wire:click="$set('showModal', true)" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
            <i class="fas fa-plus text-xs"></i>
            Novo Armazém
        </button>
    </div>

    <!-- Alertas -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 text-rose-700 dark:text-rose-400 px-4 py-3 rounded-xl font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Busca -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
        <div class="relative">
            <input wire:model.live="search" type="text" placeholder="Buscar armazém por nome..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 transition-all pl-10">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
        </div>
    </div>

    <!-- Tabela -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Armazém</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Localização</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">A                    </tr>
ções</th>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                    @forelse($this->armazens as $armazem)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/20 to-orange-600/10 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                    <i class="fas fa-warehouse"></i>
                                </div>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ $armazem->nome }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($armazem->compartilhado)
                                <span class="px-3 py-1 rounded-lg bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 text-xs font-semibold">
                                    Compartilhado
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-lg bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 text-xs font-semibold">
                                    Privado
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $armazem->ativo ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                <span class="text-xs font-medium {{ $armazem->ativo ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                    {{ $armazem->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-500">{{ $armazem->endereco ?? 'Não informado' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button wire:click="edit({{ $armazem->id }})" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-800 text-slate-400 hover:text-indigo-600 dark:hover:text-white transition-all">
                                    <i class="fas fa-edit text-sm"></i>
                                </button>
                                <button wire:click="excluirArmazem({{ $armazem->id }})" wire:confirm="Isso excluirá o armazém e suas movimentações de estoque. Confirmar?" class="p-2 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-500/20 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-all">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-warehouse text-4xl text-slate-300 dark:text-slate-600 mb-3"></i>
                                <p class="text-slate-500 font-medium">Nenhum armazém encontrado</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($this->armazens->hasPages())
        <div class="p-4 border-t border-slate-200 dark:border-dark-800">
            {{ $this->armazens->links() }}
        </div>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-dark-950/80 backdrop-blur-sm">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl w-full max-w-lg shadow-xl overflow-hidden" @click.away="$wire.showModal = false; $wire.resetForm();">
            <header class="p-6 border-b border-slate-200 dark:border-dark-800 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas {{ $editing ? 'fa-edit' : 'fa-plus' }} text-indigo-500"></i>
                    {{ $editing ? 'Editar Armazém' : 'Novo Armazém' }}
                </h3>
                <button wire:click="$set('showModal', false); resetForm();" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-all">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </header>

            <form wire:submit.prevent="save" class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nome do Armazém</label>
                    <input wire:model="nome" type="text" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 transition-all" placeholder="Ex: Galpão Principal">
                    @error('nome') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Endereço (Opcional)</label>
                    <textarea wire:model="endereco" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 transition-all" rows="2" placeholder="Endereço completo"></textarea>
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                    <div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Armazém Compartilhado?</span>
                        <p class="text-xs text-slate-500">Disponível para todas as empresas do grupo</p>
                    </div>
                    <button type="button" wire:click="$toggle('compartilhado')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $compartilhado ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $compartilhado ? 'translate-x-5' : '' }}"></div>
                    </button>
                </div>

                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                    <div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Armazém Ativo</span>
                        <p class="text-xs text-slate-500">Permitir movimentações de estoque</p>
                    </div>
                    <button type="button" wire:click="$toggle('ativo')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $ativo ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $ativo ? 'translate-x-5' : '' }}"></div>
                    </button>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-dark-800">
                    <button type="button" wire:click="$set('showModal', false); resetForm();" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-dark-700 text-slate-600 dark:text-slate-400 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-dark-800 transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
                        <i class="fas fa-save text-xs"></i>
                        {{ $editing ? 'Atualizar' : 'Criar Armazém' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
