<?php

namespace App\Livewire\Estoque;

use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\EstoqueMovimentacao;
use App\Models\EstoqueSaldo;
use App\Models\ProductSku;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $empresas = [];
    public $depositos = [];
    public $empresa_selecionada = null;
    public $deposito_selecionado = null;
    
    // Totais
    public $total_skus = 0;
    public $total_estoque = 0;
    public $total_valor = 0;
    public $estoque_minimo_itens = 0;
    
    // Dados para gráficos
    public $movimentacoes_por_tipo = [];
    public $movimentacoes_por_dia = [];
    public $top_sku = [];

    public function mount()
    {
        $this->empresas = Empresa::all();
        $this->empresa_selecionada = Auth::user()->current_empresa_id;
        $this->carregarDepositos();
        $this->carregarDados();
    }

    public function carregarDepositos()
    {
        $this->depositos = Deposito::where('ativo', true)
            ->where(function ($q) {
                $q->where('empresa_id', $this->empresa_selecionada)
                    ->orWhere('compartilhado', true);
            })
            ->get();
    }

    public function updatedEmpresaSelecionada()
    {
        $this->carregarDepositos();
        $this->deposito_selecionado = null;
        $this->carregarDados();
    }

    public function updatedDepositoSelecionado()
    {
        $this->carregarDados();
    }

    public function carregarDados()
    {
        $query = EstoqueSaldo::with(['sku', 'sku.product', 'deposito'])
            ->whereHas('deposito', function ($q) {
                $q->where('ativo', true);
                if ($this->empresa_selecionada) {
                    $q->where(function ($q2) {
                        $q2->where('empresa_id', $this->empresa_selecionada)
                            ->orWhere('compartilhado', true);
                    });
                }
                if ($this->deposito_selecionado) {
                    $q->where('id', $this->deposito_selecionado);
                }
            });

        $this->total_skus = $query->distinct('product_sku_id')->count('product_sku_id');
        $this->total_estoque = $query->sum('saldo');
        
        // Valor estimado
        $saldos = $query->get();
        $valor = 0;
        foreach ($saldos as $saldo) {
            $preco = $saldo->sku?->preco ?? $saldo->sku?->product?->preco_venda ?? 0;
            $valor += $saldo->saldo * $preco;
        }
        $this->total_valor = $valor;

        // Itens com estoque baixo (menor que 5)
        $this->estoque_minimo_itens = $query->where('saldo', '<', 5)->count();

        // Movimentações por tipo (últimos 30 dias)
        $this->movimentacoes_por_tipo = EstoqueMovimentacao::whereDate('created_at', '>=', now()->subDays(30))
            ->when($this->empresa_selecionada, fn($q) => $q->where('empresa_id', $this->empresa_selecionada))
            ->groupBy('tipo')
            ->selectRaw('tipo, SUM(quantidade) as total')
            ->pluck('total', 'tipo')
            ->toArray();

        // Movimentações por dia (últimos 7 dias)
        $this->movimentacoes_por_dia = EstoqueMovimentacao::whereDate('created_at', '>=', now()->subDays(7))
            ->when($this->empresa_selecionada, fn($q) => $q->where('empresa_id', $this->empresa_selecionada))
            ->groupBy('date')
            ->selectRaw('DATE(created_at) as date, SUM(CASE WHEN tipo = "entrada" THEN quantidade ELSE -quantidade END) as total')
            ->pluck('total', 'date')
            ->toArray();

        // Top 10 SKUs com mais saída
        $this->top_sku = EstoqueMovimentacao::where('tipo', 'saida')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->when($this->empresa_selecionada, fn($q) => $q->where('empresa_id', $this->empresa_selecionada))
            ->with('sku')
            ->groupBy('product_sku_id')
            ->selectRaw('product_sku_id, SUM(quantidade) as total_saida')
            ->orderByDesc('total_saida')
            ->limit(10)
            ->get();
    }

    public function getSaldosProperty()
    {
        return EstoqueSaldo::with(['sku', 'sku.product', 'deposito'])
            ->whereHas('deposito', function ($q) {
                $q->where('ativo', true);
                if ($this->empresa_selecionada) {
                    $q->where(function ($q2) {
                        $q2->where('empresa_id', $this->empresa_selecionada)
                            ->orWhere('compartilhado', true);
                    });
                }
                if ($this->deposito_selecionado) {
                    $q->where('id', $this->deposito_selecionado);
                }
            })
            ->orderByDesc('saldo')
            ->paginate(20);
    }

    public function getEntradasRecentesProperty()
    {
        return EstoqueMovimentacao::with(['sku', 'sku.product', 'deposito'])
            ->where('tipo', 'entrada')
            ->when($this->empresa_selecionada, fn($q) => $q->where('empresa_id', $this->empresa_selecionada))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function getSaidasRecentesProperty()
    {
        return EstoqueMovimentacao::with(['sku', 'sku.product', 'deposito'])
            ->where('tipo', 'saida')
            ->when($this->empresa_selecionada, fn($q) => $q->where('empresa_id', $this->empresa_selecionada))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.estoque.dashboard');
    }
}
