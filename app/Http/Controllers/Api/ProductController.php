<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductSku;
use App\Models\Categoria;
use App\Models\Fornecedor;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->user()->current_empresa_id;
        
        $query = Product::where('empresa_id', $empresaId)
            ->with(['categoria', 'skus']);
        
        if ($request->search) {
            $search = '%' . strtolower($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(nome) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$search]);
            });
        }
        
        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }
        
        $products = $query->orderBy('nome')->paginate(20);
        
        return response()->json($products);
    }
    
    public function show($id)
    {
        $empresaId = auth()->user()->current_empresa_id;
        
        $product = Product::where('empresa_id', $empresaId)
            ->with(['categoria', 'skus', 'variations', 'components.componentProduct'])
            ->findOrFail($id);
        
        return response()->json($product);
    }
    
    public function store(Request $request)
    {
        $empresaId = $request->user()->current_empresa_id;
        
        $validated = $request->validate([
            'nome' => 'required|min:3',
            'tipo' => 'required|in:simples,variacao,composto',
        ]);
        
        // Generate unique slug
        $slug = Str::slug($validated['nome']);
        $contador = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = Str::slug($validated['nome']) . '-' . $contador;
            $contador++;
        }
        
        $product = Product::create([
            'empresa_id' => $empresaId,
            'nome' => $validated['nome'],
            'slug' => $slug,
            'marca' => $request->marca,
            'sku' => $request->sku,
            'ean' => $request->ean,
            'descricao' => $request->descricao,
            'tipo' => $validated['tipo'],
            'categoria_id' => $request->categoria_id,
            'tags' => $request->tags ?? [],
            'ncm' => $request->ncm,
            'cest' => $request->cest,
            'origem' => $request->origem ?? '0',
            'preco_venda' => $request->preco_venda ?? 0,
            'preco_custo' => $request->preco_custo ?? 0,
            'custo_adicional' => $request->custo_adicional ?? 0,
            'peso' => $request->peso ?? 0,
            'altura' => $request->altura ?? 0,
            'largura' => $request->largura ?? 0,
            'profundidade' => $request->profundidade ?? 0,
            'estoque' => $request->estoque ?? 0,
            'ativo' => $request->ativo ?? true,
        ]);
        
        // Handle variations (simplified - create as linked products)
        if ($validated['tipo'] === 'variacao' && $request->variations) {
            foreach ($request->variations as $var) {
                Product::create([
                    'empresa_id' => $empresaId,
                    'parent_id' => $product->id,
                    'nome' => $product->nome . ' - ' . ($var['label'] ?? 'Variação'),
                    'slug' => $slug . '-' . Str::slug($var['label'] ?? 'var'),
                    'sku' => $var['sku'] ?? $product->sku . '-' . Str::slug($var['label'] ?? 'var'),
                    'tipo' => 'simples',
                    'preco_venda' => $var['preco_venda'] ?? $product->preco_venda,
                    'preco_custo' => $var['preco_custo'] ?? $product->preco_custo,
                    'estoque' => $var['estoque'] ?? 0,
                    'variation_color' => $var['color'] ?? null,
                    'variation_size' => $var['size'] ?? null,
                    'ativo' => true,
                ]);
            }
        }
        
        // Handle compound products
        if ($validated['tipo'] === 'composto' && $request->components) {
            foreach ($request->components as $index => $comp) {
                ProductComponent::create([
                    'product_id' => $product->id,
                    'component_product_id' => $comp['product_id'],
                    'quantity' => $comp['quantity'] ?? 1,
                    'unit_price' => $comp['unit_price'] ?? $comp['preco_venda'],
                    'sort_order' => $index + 1,
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'product' => $product,
            'message' => 'Produto criado com sucesso!'
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $empresaId = $request->user()->current_empresa_id;
        
        $product = Product::where('empresa_id', $empresaId)->findOrFail($id);
        
        $validated = $request->validate([
            'nome' => 'required|min:3',
            'tipo' => 'required|in:simples,variacao,composto',
        ]);
        
        // Generate unique slug
        $slug = Str::slug($validated['nome']);
        $contador = 1;
        while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = Str::slug($validated['nome']) . '-' . $contador;
            $contador++;
        }
        
        $product->update([
            'nome' => $validated['nome'],
            'slug' => $slug,
            'marca' => $request->marca,
            'sku' => $request->sku,
            'ean' => $request->ean,
            'descricao' => $request->descricao,
            'tipo' => $validated['tipo'],
            'categoria_id' => $request->categoria_id,
            'tags' => $request->tags ?? [],
            'ncm' => $request->ncm,
            'cest' => $request->cest,
            'origem' => $request->origem ?? '0',
            'preco_venda' => $request->preco_venda ?? 0,
            'preco_custo' => $request->preco_custo ?? 0,
            'custo_adicional' => $request->custo_adicional ?? 0,
            'peso' => $request->peso ?? 0,
            'altura' => $request->altura ?? 0,
            'largura' => $request->largura ?? 0,
            'profundidade' => $request->profundidade ?? 0,
            'estoque' => $request->estoque ?? 0,
            'ativo' => $request->ativo ?? true,
        ]);
        
        // Handle compound products
        if ($validated['tipo'] === 'composto') {
            $product->components()->delete();
            
            if ($request->components) {
                foreach ($request->components as $index => $comp) {
                    ProductComponent::create([
                        'product_id' => $product->id,
                        'component_product_id' => $comp['product_id'],
                        'quantity' => $comp['quantity'] ?? 1,
                        'unit_price' => $comp['unit_price'] ?? $comp['preco_venda'],
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'product' => $product,
            'message' => 'Produto atualizado com sucesso!'
        ]);
    }
    
    public function destroy(Request $request, $id)
    {
        $empresaId = $request->user()->current_empresa_id;
        
        $product = Product::where('empresa_id', $empresaId)->findOrFail($id);
        
        // Delete variations and components
        $product->variations()->delete();
        $product->components()->delete();
        $product->skus()->delete();
        $product->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Produto excluído com sucesso!'
        ]);
    }
    
    public function search(Request $request)
    {
        $empresaId = $request->user()->current_empresa_id;
        
        $products = Product::where('empresa_id', $empresaId)
            ->where('nome', 'ilike', '%' . $request->q . '%')
            ->orWhere('sku', 'ilike', '%' . $request->q . '%')
            ->limit(10)
            ->get(['id', 'nome', 'sku', 'preco_venda', 'estoque']);
        
        return response()->json($products);
    }
    
    public function categorias()
    {
        return response()->json(
            Categoria::whereNull('categoria_pai_id')
                ->with('filhas')
                ->orderBy('nome')
                ->get()
        );
    }
    
    public function fornecedores()
    {
        return response()->json(
            Fornecedor::orderBy('razao_social')->get()
        );
    }
    
    public function tags()
    {
        return response()->json(
            Tag::orderBy('nome')->get()
        );
    }
}
