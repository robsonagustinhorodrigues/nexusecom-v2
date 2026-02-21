<?php

use App\Models\Despesa;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\usesPagination;

usesPagination();

state([
    'search' => '',
    'categoria' => '',
    'status' => '',
    'dataDe' => '',
    'dataAte' => '',
    'editing' => null,
    'descricao' => '',
    'valor' => '',
    'data_pagamento' => '',
    'data_competencia' => '',
    'categoria_form' => 'outros',
    'status_form' => 'pendente',
    'forma_pagamento' => '',
    'recorrente' => false,
    'recorrencia_meses' => '',
    'fornecedor_id' => '',
    'observacoes' => '',
    'showModal' => false,
]);

rules([
    'descricao' => 'required|min:3',
    'valor' => 'required|numeric|min:0',
    'data_pagamento' => 'required|date',
    'categoria_form' => 'required',
    'status_form' => 'required',
]);

$despesas = computed(function () {
    $empresaId = Auth::user()->current_empresa_id;

    if (! $empresaId) {
        return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
    }

    return Despesa::tenant($empresaId)
        ->when($this->search, fn ($q) => $q->where('descricao', 'like', "%{$this->search}%"))
        ->when($this->categoria, fn ($q) => $q->where('categoria', $this->categoria))
        ->when($this->status, fn ($q) => $q->where('status', $this->status))
        ->when($this->dataDe, fn ($q) => $q->whereDate('data_pagamento', '>=', $this->dataDe))
        ->when($this->dataAte, fn ($q) => $q->whereDate('data_pagamento', '<=', $this->dataAte))
        ->orderBy('data_pagamento', 'desc')
        ->paginate(15);
});

$fornecedores = computed(function () {
    $empresaId = Auth::user()->current_empresa_id;

    if (! $empresaId) {
        return [];
    }

    return \App\Models\Parceiro::where('empresa_id', $empresaId)
        ->whereIn('tipo', ['fornecedor', 'ambos'])
        ->orderBy('nome')
        ->get();
});

$save = function () {
    $this->validate();

    $data = [
        'empresa_id' => Auth::user()->current_empresa_id,
        'descricao' => $this->descricao,
        'valor' => $this->valor,
        'data_pagamento' => $this->data_pagamento,
        'data_competencia' => $this->data_competencia ?: $this->data_pagamento,
        'categoria' => $this->categoria_form,
        'status' => $this->status_form,
        'forma_pagamento' => $this->forma_pagamento ?: null,
        'recorrente' => $this->recorrente,
        'recorrencia_meses' => $this->recorrente ? ($this->recorrencia_meses ?: 1) : null,
        'fornecedor_id' => $this->fornecedor_id ?: null,
        'observacoes' => $this->observacoes ?: null,
    ];

    if ($this->editing) {
        $despesa = Despesa::findOrFail($this->editing);
        $despesa->update($data);
    } else {
        Despesa::create($data);
    }

    $this->resetForm();
    $this->showModal = false;
};

$edit = function ($id) {
    $despesa = Despesa::findOrFail($id);
    $this->editing = $despesa->id;
    $this->descricao = $despesa->descricao;
    $this->valor = $despesa->valor;
    $this->data_pagamento = $despesa->data_pagamento;
    $this->data_competencia = $despesa->data_competencia;
    $this->categoria_form = $despesa->categoria;
    $this->status_form = $despesa->status;
    $this->forma_pagamento = $despesa->forma_pagamento;
    $this->recorrente = $despesa->recorrente;
    $this->recorrencia_meses = $despesa->recorrencia_meses;
    $this->fornecedor_id = $despesa->fornecedor_id;
    $this->observacoes = $despesa->observacoes;
    $this->showModal = true;
};

$delete = function ($id) {
    $despesa = Despesa::findOrFail($id);
    $despesa->delete();
};

$openModal = function () {
    $this->resetForm();
    $this->showModal = true;
};

$closeModal = function () {
    $this->showModal = false;
    $this->resetForm();
};

$resetForm = function () {
    $this->editing = null;
    $this->descricao = '';
    $this->valor = '';
    $this->data_pagamento = date('Y-m-d');
    $this->data_competencia = '';
    $this->categoria_form = 'outros';
    $this->status_form = 'pendente';
    $this->forma_pagamento = '';
    $this->recorrente = false;
    $this->recorrencia_meses = '';
    $this->fornecedor_id = '';
    $this->observacoes = '';
};

$getCategorias = function () {
    return Despesa::getCategorias();
};

$categoriasList = function () {
    return Despesa::getCategorias();
};

$getTotais = computed(function () {
    $empresaId = Auth::user()->current_empresa_id;

    if (! $empresaId) {
        return ['qtd' => 0, 'total' => 0, 'pago' => 0, 'pendente' => 0];
    }

    $query = Despesa::tenant($empresaId);

    return [
        'qtd' => $query->count(),
        'total' => $query->sum('valor'),
        'pago' => (clone $query)->where('status', 'pago')->sum('valor'),
        'pendente' => (clone $query)->where('status', 'pendente')->sum('valor'),
    ];
});
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center shadow-lg shadow-rose-500/20">
                    <i class="fas fa-receipt text-white text-lg"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Despesas</h2>
                    <p class="text-sm text-slate-500">Cadastro de despesas do DRE</p>
                </div>
            </div>
            
            <button 
                wire:click="openModal"
                class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-semibold transition-all flex items-center gap-2"
            >
                <i class="fas fa-plus"></i>
                Nova Despesa
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    wire:model.live="search" 
                    placeholder="Buscar despesa..."
                    class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500"
                >
            </div>
            <select wire:model.live="categoria" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500">
                <option value="">Todas categorias</option>
                @foreach($categoriasList() as $key => $name)
                    <option value="{{ $key }}">{{ $name }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500">
                <option value="">Todos status</option>
                <option value="pendente">Pendente</option>
                <option value="pago">Pago</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <input type="date" wire:model.live="dataDe" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500">
            <input type="date" wire:model.live="dataAte" class="bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500">
        </div>
    </div>

    <!-- Totais -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-1">Total</div>
            <div class="text-xl font-bold text-slate-900 dark:text-white">{{ $totais['qtd'] }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-1">Valor Total</div>
            <div class="text-xl font-bold text-rose-500">R$ {{ number_format($totais['total'], 2, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-1">Pago</div>
            <div class="text-xl font-bold text-emerald-500">R$ {{ number_format($totais['pago'], 2, ',', '.') }}</div>
        </div>
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider mb-1">Pendente</div>
            <div class="text-xl font-bold text-amber-500">R$ {{ number_format($totais['pendente'], 2, ',', '.') }}</div>
        </div>
    </div>

    <!-- Lista -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <table class="w-full">
            <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Data</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Descrição</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoria</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Valor</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                @forelse($despesas as $despesa)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50">
                        <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                            {{ \Carbon\Carbon::parse($despesa->data_pagamento)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $despesa->descricao }}</div>
                            @if($despesa->fornecedor)
                                <div class="text-xs text-slate-500">{{ $despesa->fornecedor->nome }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300">
                                {{ $categoriasList()[$despesa->categoria] ?? $despesa->categoria }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($despesa->status === 'pago')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400">
                                    Pago
                                </span>
                            @elseif($despesa->status === 'pendente')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400">
                                    Pendente
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-dark-800 text-slate-500">
                                    Cancelado
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-rose-500">
                            R$ {{ number_format($despesa->valor, 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit({{ $despesa->id }})" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button wire:click="delete({{ $despesa->id }})" wire:confirm="Excluir esta despesa?" class="text-slate-400 hover:text-rose-500">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-receipt text-3xl text-slate-300 dark:text-dark-700 mb-3"></i>
                                <p class="text-sm">Nenhuma despesa encontrada</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($despesas->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-dark-800">
                {{ $despesas->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal -->
@if($showModal)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-dark-950/80 backdrop-blur-sm">
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-dark-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-receipt text-rose-500"></i>
                {{ $editing ? 'Editar' : 'Nova' }} Despesa
            </h3>
        </div>
        
        <form wire:submit="save" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descrição *</label>
                <input type="text" wire:model="descricao" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500" required>
                @error('descricao') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Valor *</label>
                    <input type="number" step="0.01" wire:model="valor" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500" required>
                    @error('valor') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Data Pagamento *</label>
                    <input type="date" wire:model="data_pagamento" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500" required>
                    @error('data_pagamento') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Categoria *</label>
                    <select wire:model="categoria_form" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500" required>
                        @foreach($categoriasList() as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status *</label>
                    <select wire:model="status_form" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500" required>
                        <option value="pendente">Pendente</option>
                        <option value="pago">Pago</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Forma de Pagamento</label>
                <select wire:model="forma_pagamento" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500">
                    <option value="">Selecione...</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="pix">PIX</option>
                    <option value="transferencia">Transferência</option>
                    <option value="boleto">Boleto</option>
                    <option value="cartao_credito">Cartão de Crédito</option>
                    <option value="cartao_debito">Cartão de Débito</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="recorrente" class="w-4 h-4 text-rose-500 border-slate-300 dark:border-dark-600 rounded focus:ring-rose-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">Despesa recorrente</span>
                </label>
                @if($recorrente)
                    <div class="flex items-center gap-2">
                        <input type="number" wire:model="recorrencia_meses" min="1" placeholder="Meses" class="w-20 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-3 py-1.5 text-sm font-medium focus:border-rose-500">
                        <span class="text-sm text-slate-500">meses</span>
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fornecedor</label>
                <select wire:model="fornecedor_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500">
                    <option value="">Selecione...</option>
                    @foreach($fornecedores as $fornecedor)
                        <option value="{{ $fornecedor->id }}">{{ $fornecedor->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Observações</label>
                <textarea wire:model="observacoes" rows="2" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-2.5 text-sm font-medium focus:border-rose-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" wire:click="closeModal" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-dark-800 text-slate-700 dark:text-slate-300 font-semibold text-sm hover:bg-slate-200 dark:hover:bg-dark-700 transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-semibold text-sm transition-all">
                    {{ $editing ? 'Atualizar' : 'Cadastrar' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif
