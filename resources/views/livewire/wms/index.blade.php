<?php

use function Livewire\Volt\{state, computed, rules, usesPagination};
use App\Models\ProductSku;
use App\Models\Armazem;
use App\Models\EstoqueMovimentacao;
use App\Models\EstoqueSaldo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

usesPagination();

state([
    'showModal' => false,
    'selectedSkuId' => null,
    'armazem_id' => '',
    'quantidade' => 1,
    'tipo' => 'entrada',
    'observacao' => '',
    'search' => '',
    'filtroArmazem' => '',
    'showHistoryModal' => false,
    'history' => [],
    'historySkuName' => '',
]);

rules([
    'armazem_id' => 'required|exists:armazens,id',
    'quantidade' => 'required|integer|min:1',
    'tipo' => 'required|in:entrada,saida,ajuste',
]);

$skus = computed(function () {
    return ProductSku::with('product')
        ->with(['saldos' => function($q) {
            if ($this->filtroArmazem) {
                $q->where('armazem_id', $this->filtroArmazem);
            }
        }])
        ->when($this->search, function($q) {
            $q->where('sku', 'like', "%{$this->search}%")
              ->orWhere('label', 'like', "%{$this->search}%")
              ->orWhereHas('product', fn($pq) => $pq->where('nome', 'like', "%{$this->search}%"));
        })
        ->orderBy('sku')
        ->paginate(15);
});

$armazens = computed(function () {
    return Armazem::orderBy('nome')->get();
});

$obterSaldoPorSku = function($sku) {
    if ($this->filtroArmazem) {
        $saldo = $sku->saldos->first();
        return $saldo ? $saldo->saldo : 0;
    }
    return EstoqueSaldo::where('product_sku_id', $sku->id)->sum('saldo');
};

$abrirMovimentacao = function ($skuId) {
    $this->selectedSkuId = $skuId;
    $this->showModal = true;
};

$verHistorico = function ($skuId) {
    $sku = ProductSku::with('product')->find($skuId);
    $this->historySkuName = "{$sku->product->nome} — {$sku->label}";
    $this->history = EstoqueMovimentacao::where('product_sku_id', $skuId)
        ->with(['armazem', 'user'])
        ->latest()
        ->take(50)
        ->get();
    $this->showHistoryModal = true;
};

$salvarMovimentacao = function () {
    $this->validate();

    DB::transaction(function () {
        $quantidadeMov = $this->tipo === 'saida' ? -$this->quantidade : $this->quantidade;
        
        // Registra a movimentação
        EstoqueMovimentacao::create([
            'product_sku_id' => $this->selectedSkuId,
            'armazem_id' => $this->armazem_id,
            'user_id' => Auth::id(),
            'quantidade' => $quantidadeMov,
            'tipo' => $this->tipo,
            'observacao' => $this->observacao,
            'origem' => 'Terminal WMS',
        ]);

        // Atualiza saldo por armazém
        EstoqueSaldo::atualizarSaldo($this->selectedSkuId, $this->armazem_id, $quantidadeMov);

        // Atualiza o estoque consolidado no SKU
        $sku = ProductSku::find($this->selectedSkuId);
        $novoSaldo = EstoqueSaldo::where('product_sku_id', $this->selectedSkuId)->sum('saldo');
        $sku->estoque = $novoSaldo;
        $sku->save();
    });

    $this->reset(['showModal', 'selectedSkuId', 'armazem_id', 'quantidade', 'tipo', 'observacao']);
    session()->flash('message', 'Estoque atualizado com sucesso! ⚡');
};

?>

<div class="space-y-6">
    <header>
        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight italic uppercase text-indigo-500">WMS — Estoque Central ⚡</h2>
        <p class="text-slate-500 font-medium font-bold italic">Visão consolidada de inventário e movimentações em tempo real.</p>
    </header>

    @if (session()->has('message'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl font-bold italic uppercase tracking-widest text-xs">
            {{ session('message') }}
        </div>
    @endif

    <!-- BUSCA -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 p-4 rounded-3xl shadow-xl">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2 relative">
                <input wire:model.live="search" type="text" placeholder="Buscar por SKU, Nome do Produto ou Variação..." class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-2xl px-5 py-4 text-slate-900 dark:text-white focus:border-indigo-500 transition-all pl-12 font-bold uppercase italic tracking-tighter text-sm">
                <i class="fas fa-barcode absolute left-5 top-5 text-indigo-600 font-bold text-xl"></i>
            </div>
            <div>
                <select wire:model.live="filtroArmazem" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-2xl px-4 py-4 text-slate-900 dark:text-white focus:border-indigo-500 transition-all font-bold text-sm h-[54px]">
                    <option value="">Todos os Armazéns</option>
                    @foreach($this->armazens as $az)
                        <option value="{{ $az->id }}">{{ $az->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center">
                <button wire:click="$set('filtroArmazem', '')" class="px-4 py-3 rounded-2xl bg-slate-100 dark:bg-dark-800 text-slate-500 font-bold text-xs uppercase hover:bg-slate-200 dark:hover:bg-dark-700 transition-all w-full">
                    <i class="fas fa-times mr-2"></i> Limpar Filtro
                </button>
            </div>
        </div>
    </div>

    <!-- GRID DE PRODUTOS -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-3xl overflow-hidden shadow-2xl">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Produto / Variação</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic">SKU</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-center">Saldo</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest italic text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                @forelse($this->skus as $sku)
                <tr class="hover:bg-dark-800/30 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-xs font-black text-slate-500 uppercase italic">{{ $sku->product->nome }}</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white uppercase italic tracking-tighter">{{ $sku->label }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs text-indigo-400 font-bold tracking-widest uppercase">
                        {{ $sku->sku }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $saldoExibicao = $filtroArmazem 
                                ? ($sku->saldos->first()?->saldo ?? 0)
                                : \App\Models\EstoqueSaldo::where('product_sku_id', $sku->id)->sum('saldo');
                        @endphp
                        <span class="text-xl font-black text-slate-900 dark:text-white italic">
                           {{ number_format($saldoExibicao, 0) }}
                        </span>
                        <span class="text-[9px] text-slate-600 font-bold uppercase italic ml-1">Unid.</span>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $estoqueStatus = $saldoExibicao;
                        @endphp
                        @if($estoqueStatus > 10)
                            <span class="bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 px-3 py-1 rounded-lg text-[9px] font-black uppercase italic">Saudável</span>
                        @elseif($estoqueStatus > 0)
                            <span class="bg-amber-500/10 text-amber-500 border border-amber-500/20 px-3 py-1 rounded-lg text-[9px] font-black uppercase italic">Baixo</span>
                        @else
                            <span class="bg-rose-500/10 text-rose-500 border border-rose-500/20 px-3 py-1 rounded-lg text-[9px] font-black uppercase italic">Esgotado</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <button wire:click="verHistorico({{ $sku->id }})" class="p-2 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all" title="Ver Histórico">
                                <i class="fas fa-history"></i>
                            </button>
                            <button wire:click="abrirMovimentacao({{ $sku->id }})" class="px-4 py-2 rounded-xl bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-indigo-600 hover:border-indigo-500 transition-all flex items-center gap-2 text-[10px] font-black uppercase italic">
                                <i class="fas fa-exchange-alt"></i> Mover
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-700 font-bold italic uppercase">Nenhum SKU cadastrado para movimentação.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-6 bg-slate-50 dark:bg-dark-950 border-t border-slate-100 dark:border-dark-800">
            {{ $this->skus->links() }}
        </div>
    </div>

    <!-- MODAL DE MOVIMENTAÇÃO -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 dark:bg-dark-950/90 backdrop-blur-md">
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-[2.5rem] w-full max-w-lg shadow-[0_0_50px_rgba(79,70,229,0.1)] overflow-hidden">
            <header class="p-8 border-b border-slate-100 dark:border-dark-800 flex justify-between items-center bg-slate-50 dark:bg-dark-950/50">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white italic uppercase tracking-tight">Movimentar Estoque ⚡</h3>
                    <p class="text-[10px] text-slate-500 font-bold uppercase italic mt-1">Lançamento de Entrada ou Saída Manual</p>
                </div>
                <button wire:click="$set('showModal', false)" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-dark-800 flex items-center justify-center text-slate-500 hover:text-slate-900 dark:hover:text-white transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </header>

            <form wire:submit.prevent="salvarMovimentacao" class="p-8 space-y-6">
                <!-- Seletor de Tipo -->
                <div class="flex gap-2 p-1 bg-dark-950 border border-dark-800 rounded-2xl">
                    <button type="button" wire:click="$set('tipo', 'entrada')" class="flex-1 py-3 rounded-xl font-black text-[10px] uppercase italic tracking-widest transition-all {{ $tipo === 'entrada' ? 'bg-emerald-600 text-white shadow-lg' : 'text-slate-500 hover:text-white' }}">
                        <i class="fas fa-plus-circle mr-1"></i> Entrada
                    </button>
                    <button type="button" wire:click="$set('tipo', 'saida')" class="flex-1 py-3 rounded-xl font-black text-[10px] uppercase italic tracking-widest transition-all {{ $tipo === 'saida' ? 'bg-rose-600 text-white shadow-lg' : 'text-slate-500 hover:text-white' }}">
                        <i class="fas fa-minus-circle mr-1"></i> Saída
                    </button>
                    <button type="button" wire:click="$set('tipo', 'ajuste')" class="flex-1 py-3 rounded-xl font-black text-[10px] uppercase italic tracking-widest transition-all {{ $tipo === 'ajuste' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-500 hover:text-white' }}">
                        <i class="fas fa-cog mr-1"></i> Ajuste
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 italic px-1">Selecione o Armazém</label>
                        <select wire:model="armazem_id" class="w-full bg-dark-950 border border-dark-700 rounded-xl px-4 py-3 text-white focus:border-indigo-500 transition-all font-bold uppercase tracking-tight italic h-[50px]">
                            <option value="">-- SELECIONE --</option>
                            @foreach($this->armazens as $az)
                                <option value="{{ $az->id }}">{{ $az->nome }}</option>
                            @endforeach
                        </select>
                        @error('armazem_id') <span class="text-rose-500 text-[9px] font-black uppercase italic px-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 italic px-1">Quantidade</label>
                        <input wire:model="quantidade" type="number" class="w-full bg-dark-950 border border-dark-700 rounded-xl px-4 py-3 text-white focus:border-indigo-500 transition-all font-black text-xl italic" placeholder="0">
                        @error('quantidade') <span class="text-rose-500 text-[9px] font-black uppercase italic px-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col justify-end pb-3 text-[9px] font-bold text-slate-600 uppercase italic">
                        * A quantidade será {{ $tipo === 'saida' ? 'subtraída' : 'adicionada' }} do saldo atual.
                    </div>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 italic px-1">Observação / Motivo</label>
                    <textarea wire:model="observacao" class="w-full bg-dark-950 border border-dark-700 rounded-xl px-4 py-3 text-white focus:border-indigo-500 transition-all font-bold uppercase tracking-tight italic" rows="2" placeholder="EX: ENTRADA DE NOTA FISCAL, EXTRAVIO, AJUSTE DE INVENTÁRIO..."></textarea>
                </div>

                <button type="submit" class="w-full py-5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black shadow-xl shadow-indigo-600/20 transition-all flex items-center justify-center gap-3 uppercase italic tracking-widest text-sm">
                    <i class="fas fa-check-double"></i> Confirmar Movimentação
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- MODAL DE HISTÓRICO -->
    @if($showHistoryModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-dark-950/90 backdrop-blur-md">
        <div class="bg-dark-900 border border-dark-800 rounded-[2.5rem] w-full max-w-2xl shadow-2xl overflow-hidden">
            <header class="p-8 border-b border-dark-800 flex justify-between items-center bg-dark-950/50">
                <div>
                    <h3 class="text-xl font-black text-white italic uppercase tracking-tight">Linha do Tempo de Estoque ⚡</h3>
                    <p class="text-[10px] text-indigo-500 font-bold uppercase italic mt-1">{{ $historySkuName }}</p>
                </div>
                <button wire:click="$set('showHistoryModal', false)" class="w-10 h-10 rounded-full bg-dark-800 flex items-center justify-center text-slate-500 hover:text-white transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </header>

            <div class="p-8 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <div class="space-y-4">
                    @forelse($history as $h)
                    <div class="flex gap-4 items-start p-4 bg-dark-950 border border-dark-800 rounded-2xl group/item">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $h->quantidade > 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500' }}">
                            <i class="fas {{ $h->quantidade > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-black text-white uppercase italic">{{ $h->tipo }} ({{ number_format($h->quantidade, 0) }})</span>
                                <span class="text-[9px] text-slate-600 font-bold uppercase italic">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex gap-3 text-[9px] font-bold uppercase italic text-slate-500">
                                <span class="flex items-center gap-1 text-indigo-400"><i class="fas fa-warehouse text-[8px]"></i> {{ $h->armazem->nome }}</span>
                                <span class="flex items-center gap-1"><i class="fas fa-user text-[8px]"></i> {{ $h->user->name ?? 'Sistema' }}</span>
                            </div>
                            @if($h->observacao)
                                <p class="text-[10px] text-slate-400 italic mt-2 border-l-2 border-dark-700 pl-3 leading-relaxed">{{ $h->observacao }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-center py-8 text-slate-600 font-bold uppercase italic">Nenhuma movimentação registrada.</p>
                    @endforelse
                </div>
            </div>

            <footer class="p-8 bg-dark-950/50 border-t border-dark-800 flex justify-end">
                <button wire:click="$set('showHistoryModal', false)" class="px-8 py-3 rounded-xl bg-dark-800 text-slate-400 font-bold hover:text-white transition-all uppercase tracking-tight italic text-xs">FECHAR PORTAL</button>
            </footer>
        </div>
    </div>
    @endif
</div>
