<?php

namespace App\Livewire\Estoque;

use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\EstoqueMovimentacao;
use App\Models\ProductSku;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Movimentacoes extends Component
{
    use WithPagination;

    public $depositos = [];
    
    public $empresas = [];
    
    public $skus = [];
    
    public $filtroEmpresa = '';
    
    public $filtroDeposito = '';
    
    public $filtroTipo = '';
    
    public $filtroDataDe = '';
    
    public $filtroDataAte = '';
    
    public $filtroSKU = '';
    
    public $showModal = false;
    
    // Campos do formulário
    public $movimentacaoId = null;
    public $tipo = 'entrada';
    public $product_sku_id = '';
    public $deposito_id = '';
    public $quantidade = 0;
    public $documento_tipo = '';
    public $documento = '';
    public $valor_unitario = 0;
    public $observacao = '';
    public $produto_bom = true;

    public function mount()
    {
        $this->empresas = Empresa::all();
        $this->depositos = Deposito::where('ativo', true)->get();
        $this->filtroDataDe = now()->subDays(30)->format('Y-m-d');
        $this->filtroDataAte = now()->format('Y-m-d');
    }

    public function getMovimentacoesProperty()
    {
        return EstoqueMovimentacao::with(['sku', 'deposito', 'empresa'])
            ->when($this->filtroEmpresa, fn($q) => $q->where('empresa_id', $this->filtroEmpresa))
            ->when($this->filtroDeposito, fn($q) => $q->where('deposito_id', $this->filtroDeposito))
            ->when($this->filtroTipo, fn($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->filtroDataDe, fn($q) => $q->whereDate('created_at', '>=', $this->filtroDataDe))
            ->when($this->filtroDataAte, fn($q) => $q->whereDate('created_at', '<=', $this->filtroDataAte))
            ->when($this->filtroSKU, fn($q) => $q->whereHas('sku', fn($q2) => $q2->where('sku', 'like', "%{$this->filtroSKU}%")))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function buscarSKUs($search)
    {
        if (strlen($search) < 2) {
            return [];
        }
        
        return ProductSku::where('sku', 'like', "%{$search}%")
            ->with('product')
            ->limit(10)
            ->get()
            ->map(fn($sku) => [
                'id' => $sku->id,
                'text' => $sku->sku . ' - ' . ($sku->product?->nome ?? 'Sem produto'),
            ]);
    }

    public function novaMovimentacao()
    {
        $this->reset(['movimentacaoId', 'tipo', 'product_sku_id', 'deposito_id', 'quantidade', 'documento_tipo', 'documento', 'valor_unitario', 'observacao', 'produto_bom']);
        $this->deposito_id = $this->depositos->first()?->id;
        $this->showModal = true;
    }

    public function salvar()
    {
        $this->validate([
            'tipo' => 'required|in:entrada,saida',
            'product_sku_id' => 'required|exists:product_skus,id',
            'deposito_id' => 'required|exists:depositos,id',
            'quantidade' => 'required|integer|min:1',
        ]);

        $dados = [
            'product_sku_id' => $this->product_sku_id,
            'deposito_id' => $this->deposito_id,
            'quantidade' => $this->quantidade,
            'tipo' => $this->tipo,
            'documento_tipo' => $this->documento_tipo ?: null,
            'documento' => $this->documento ?: null,
            'valor_unitario' => $this->valor_unitario ?: null,
            'observacao' => $this->observacao,
            'empresa_id' => Auth::user()->current_empresa_id,
        ];

        $service = app(\App\Services\EstoqueMovimentacaoService::class);

        if ($this->tipo === 'entrada' && $this->documento_tipo === 'nfe_devolucao') {
            $dados['produto_bom'] = $this->produto_bom;
            $service->registrarDevolucao($dados);
        } elseif ($this->tipo === 'entrada') {
            $service->registrarEntrada($dados);
        } else {
            $service->registrarSaida($dados);
        }

        $this->showModal = false;
        session()->flash('success', 'Movimentação registrada com sucesso!');
    }

    public function estornar(int $movimentacaoId)
    {
        $service = app(\App\Services\EstoqueMovimentacaoService::class);
        $result = $service->estornar($movimentacaoId, 'Estorno via interface');
        
        if ($result) {
            session()->flash('success', 'Movimentação estornada com sucesso!');
        } else {
            session()->flash('error', 'Não foi possível estornar esta movimentação.');
        }
    }

    public function getTipoLabel($tipo)
    {
        return match($tipo) {
            'entrada' => 'Entrada',
            'saida' => 'Saída',
            'perda' => 'Perda',
            default => $tipo,
        };
    }

    public function getDocTipoLabel($tipo)
    {
        return match($tipo) {
            'nfe_compra' => 'NF-e Compra',
            'nfe_devolucao' => 'NF-e Devolução',
            'pedido_venda' => 'Pedido Venda',
            'ajuste' => 'Ajuste',
            'transferencia' => 'Transferência',
            default => $tipo ?? '-',
        };
    }

    public function render()
    {
        return view('livewire.estoque.movimentacoes');
    }
}
