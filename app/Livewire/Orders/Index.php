<?php

namespace App\Livewire\Orders;

use App\Jobs\SyncMeliPedidosJob;
use App\Models\Integracao;
use App\Models\MarketplacePedido;
use App\Models\Tarefa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    public $status = 'em_aberto';

    public $marketplace = '';

    public $dataDe = '';

    public $dataAte = '';

    public $isSyncing = false;

    public $syncMessage = '';

    public $viewMode = 'cards';

    public function mount()
    {
        $this->dataDe = now()->subDays(30)->format('Y-m-d');
        $this->dataAte = now()->format('Y-m-d');
    }

    public function toggleView($mode)
    {
        $this->viewMode = $mode;
    }

    public function getIntegracoesProperty()
    {
        return Integracao::where('empresa_id', Auth::user()->current_empresa_id)
            ->where('marketplace', '!=', 'bling')
            ->where('ativo', true)
            ->get();
    }

    public function getPedidosProperty()
    {
        $empresaId = Auth::user()->current_empresa_id;

        return MarketplacePedido::tenant($empresaId)
            ->when($this->search, fn ($q) => $q->where('pedido_id', 'like', "%{$this->search}%")
                ->orWhere('comprador_nome', 'like', "%{$this->search}%"))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->marketplace, fn ($q) => $q->where('marketplace', $this->marketplace))
            ->when($this->dataDe, fn ($q) => $q->whereDate('data_compra', '>=', $this->dataDe))
            ->when($this->dataAte, fn ($q) => $q->whereDate('data_compra', '<=', $this->dataAte))
            ->with('integracao')
            ->orderBy('data_compra', 'desc')
            ->paginate(15);
    }

    public function getTotaisProperty()
    {
        $empresaId = Auth::user()->current_empresa_id;

        $query = MarketplacePedido::tenant($empresaId)
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->marketplace, fn ($q) => $q->where('marketplace', $this->marketplace))
            ->when($this->dataDe, fn ($q) => $q->whereDate('data_compra', '>=', $this->dataDe))
            ->when($this->dataAte, fn ($q) => $q->whereDate('data_compra', '<=', $this->dataAte))
            ->when($this->search, fn ($q) => $q->where('pedido_id', 'like', "%{$this->search}%")
                ->orWhere('comprador_nome', 'like', "%{$this->search}%"));

        return [
            'qtd' => $query->count(),
            'valor' => $query->sum('valor_total'),
            'frete' => $query->sum('valor_frete'),
            'taxas' => $query->sum(DB::raw('COALESCE(valor_taxa_platform, 0) + COALESCE(valor_taxa_pagamento, 0) + COALESCE(valor_taxa_fixa, 0) + COALESCE(valor_outros, 0)')),
            'liquido' => $query->sum('valor_liquido'),
        ];
    }

    public function syncMeli()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $userId = Auth::id();
        $this->isSyncing = true;
        $this->syncMessage = '';

        $tarefa = Tarefa::create([
            'user_id' => $userId,
            'empresa_id' => $empresaId,
            'tipo' => 'sync_meli_pedidos',
            'status' => 'pending',
            'total' => 1000,
            'processado' => 0,
            'sucesso' => 0,
            'falha' => 0,
            'mensagem' => 'Aguardando processamento...',
            'started_at' => now(),
        ]);

        SyncMeliPedidosJob::dispatch($empresaId, $userId, $tarefa->id, 50, 20, 1000);

        $this->syncMessage = 'Sincronização iniciada. Acompanhe em: Administração > Tarefas';
        $this->isSyncing = false;
    }

    public function render()
    {
        return view('livewire.orders.index');
    }
}
