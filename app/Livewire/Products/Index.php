<?php

namespace App\Livewire\Products;

use App\Models\Categoria;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    public $categoria_id = '';

    public $tipo = '';

    protected $queryString = ['search', 'categoria_id', 'tipo'];

    public function toggleStatus(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['ativo' => ! $product->ativo]);
    }

    public function deleteProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->delete();
        session()->flash('message', 'Produto excluído com sucesso!');
    }

    public function render()
    {
        $empresaId = \Illuminate\Support\Facades\Auth::user()->current_empresa_id;

        $products = Product::with(['categoria', 'skus'])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($this->search, fn ($q) => $q->where('nome', 'like', "%{$this->search}%"))
            ->when($this->categoria_id, fn ($q) => $q->where('categoria_id', $this->categoria_id))
            ->when($this->tipo, fn ($q) => $q->where('tipo', $this->tipo))
            ->orderBy('nome')
            ->paginate(12);

        $categorias = Categoria::whereNull('categoria_pai_id')
            ->with('filhas')
            ->orderBy('nome')
            ->get();

        $stats = [
            'total' => Product::when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))->count(),
            'simples' => Product::when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))->where('tipo', 'simples')->count(),
            'variacao' => Product::when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))->where('tipo', 'variacao')->count(),
        ];

        return view('livewire.products.index', [
            'products' => $products,
            'categorias' => $categorias,
            'stats' => $stats,
        ]);
    }
}
