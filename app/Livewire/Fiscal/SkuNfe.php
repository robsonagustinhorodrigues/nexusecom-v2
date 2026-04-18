<?php

namespace App\Livewire\Fiscal;

use App\Models\Empresa;
use App\Models\NfeItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class SkuNfe extends Component
{
    use WithPagination;

    public $empresaId;

    public $search = '';

    public $skuSelecionado = null;

    public $produtoSelecionado = null;

    public $itensAgrupados = [];

    public $showModal = false;

    public function mount()
    {
        $this->empresaId = Auth::user()?->current_empresa_id;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $empresaId = (int) $this->empresaId;

        if (! $empresaId) {
            return view('livewire.fiscal.sku-nfe', [
                'empresas' => collect(),
                'itens' => collect(),
                'produtos' => collect(),
            ]);
        }

        $user = Auth::user();
        if (! $user) {
            return view('livewire.fiscal.sku-nfe', [
                'empresas' => collect(),
                'itens' => collect(),
                'produtos' => collect(),
            ]);
        }

        $empresas = Empresa::where('grupo_id', $user->grupo_id)
            ->orderBy('nome')
            ->get();

        $itens = NfeItem::query()
            ->whereNull('product_id')
            ->where(function ($q) use ($empresaId) {
                $q->whereHas('nfeEmitida', function ($q2) use ($empresaId) {
                    $q2->where('empresa_id', $empresaId);
                })->orWhereHas('nfeRecebida', function ($q2) use ($empresaId) {
                    $q2->where('empresa_id', $empresaId);
                });
            })
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('codigo_produto', 'like', '%'.$this->search.'%')
                        ->orWhere('gtin', 'like', '%'.$this->search.'%')
                        ->orWhere('descricao', 'like', '%'.$this->search.'%');
                });
            })
            ->select('codigo_produto', 'gtin', 'descricao', 'ncm')
            ->distinct()
            ->orderBy('codigo_produto')
            ->paginate(20);

        $produtos = Product::where('empresa_id', $empresaId)
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('nome', 'like', '%'.$this->search.'%')
                        ->orWhere('ean', 'like', '%'.$this->search.'%')
                        ->orWhereHas('skus', function ($q3) {
                            $q3->where('sku', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderBy('nome')
            ->limit(20)
            ->get();

        return view('livewire.fiscal.sku-nfe', compact('empresas', 'itens', 'produtos'));
    }

    public function abrirAssociar($codigoProduto)
    {
        $this->skuSelecionado = $codigoProduto;
        $this->produtoSelecionado = null;
        $this->showModal = true;
    }

    public function associar()
    {
        if (! $this->skuSelecionado || ! $this->produtoSelecionado) {
            return;
        }

        $empresaId = (int) $this->empresaId;

        $itens = NfeItem::where('codigo_produto', $this->skuSelecionado)
            ->where(function ($q) use ($empresaId) {
                $q->whereHas('nfeEmitida', function ($q2) use ($empresaId) {
                    $q2->where('empresa_id', $empresaId);
                })->orWhereHas('nfeRecebida', function ($q2) use ($empresaId) {
                    $q2->where('empresa_id', $empresaId);
                });
            })
            ->whereNull('product_id')
            ->get();

        foreach ($itens as $item) {
            $item->update(['product_id' => $this->produtoSelecionado]);
        }

        $this->showModal = false;
        $this->skuSelecionado = null;
        $this->produtoSelecionado = null;

        session()->flash('message', 'Itens associados com sucesso!');
    }

    public function changeEmpresa($empresaId)
    {
        $this->empresaId = (int) $empresaId;
        $this->resetPage();
    }
}
