<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Fornecedor;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->get('empresa', $request->get('empresa_id', session('empresa_id', 6)));
        $empresa = \App\Models\Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        $query = Product::where('grupo_id', $grupoId)
            ->with(['categoria', 'skus']);

        if ($request->search) {
            $search = '%'.strtolower($request->search).'%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(nome) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$search]);
            });
        }

        if ($request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('status') && $request->status !== '') {
            if ($request->status === '1' || $request->status === 'true') {
                $query->where('ativo', true);
            } elseif ($request->status === '0' || $request->status === 'false') {
                $query->where('ativo', false);
            }
        }

        $products = $query->orderBy('nome')->paginate(20);

        return response()->json($products);
    }

    public function show($id)
    {
        $empresaId = session('empresa_id', 6);
        $empresa = \App\Models\Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        $product = Product::where('grupo_id', $grupoId)
            ->with(['categoria', 'skus', 'variations', 'components.componentProduct'])
            ->findOrFail($id);

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $empresaId = $request->input('empresa', session('empresa_id', 6));
        $empresa = \App\Models\Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        $validated = $request->validate([
            'nome' => 'required|min:3',
            'tipo' => 'required|in:simples,variacao,composto',
            'sku' => 'required|unique:products,sku,null,id,grupo_id,'.$grupoId,
        ]);

        $slug = Str::slug($validated['nome']);
        $contador = 1;
        while (Product::where('slug', $slug)->where('grupo_id', $grupoId)->exists()) {
            $slug = Str::slug($validated['nome']).'-'.$contador;
            $contador++;
        }

        $product = Product::create([
            'empresa_id' => $empresaId,
            'grupo_id' => $grupoId,
            'nome' => $validated['nome'],
            'slug' => $slug,
            'marca' => $request->marca,
            'sku' => $validated['sku'],
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
            'ativo' => $request->ativo ?? true,
        ]);

        if ($validated['tipo'] === 'variacao' && $request->variations) {
            $varCounter = 1;
            foreach ($request->variations as $var) {
                $varNome = $var['nome'] ?? 'Variação '.$varCounter;
                $varSlug = $slug.'-'.Str::slug($varNome);
                $varSku = $var['sku'] ?? $validated['sku'].'-'.$varCounter;
                $herdar = $var['herdar'] ?? true;

                while (Product::where('slug', $varSlug)->where('grupo_id', $empresa?->grupo_id)->exists()) {
                    $varSlug = $slug.'-'.Str::slug($varNome).'-'.$varCounter;
                    $varCounter++;
                }

                $varData = [
                    'empresa_id' => $product->empresa_id,
                    'grupo_id' => $product->grupo_id, // Herdar grupo_id do pai
                    'parent_id' => $product->id,
                    'nome' => $product->nome.' - '.$varNome,
                    'slug' => $varSlug,
                    'sku' => $varSku,
                    'tipo' => 'simples',
                    'variation_color' => $var['color'] ?? null,
                    'variation_size' => $var['size'] ?? null,
                    'herdar' => $herdar,
                    'ativo' => true,
                ];

                if (! $herdar) {
                    $varData['preco_venda'] = $var['preco_venda'] ?? $product->preco_venda;
                    $varData['preco_custo'] = $var['preco_custo'] ?? $product->preco_custo;
                    $varData['marca'] = $var['marca'] ?? $product->marca;
                    $varData['ncm'] = $var['ncm'] ?? $product->ncm;
                } else {
                    $varData['preco_venda'] = $product->preco_venda;
                    $varData['preco_custo'] = $product->preco_custo;
                    $varData['marca'] = $product->marca;
                    $varData['ncm'] = $product->ncm;
                }

                Product::create($varData);
                $varCounter++;
            }
        }

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
            'message' => 'Produto criado com sucesso!',
        ]);
    }

    public function update(Request $request, $id)
    {
        $empresaId = $request->input('empresa', session('empresa_id', 6));
        $empresa = \App\Models\Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        $product = Product::where('grupo_id', $grupoId)->findOrFail($id);

        $validated = $request->validate([
            'nome' => 'required|min:3',
            'tipo' => 'required|in:simples,variacao,composto',
            'sku' => 'required|unique:products,sku,'.$id.',id,grupo_id,'.$grupoId,
        ]);

        $slug = Str::slug($validated['nome']);
        $contador = 1;
        while (Product::where('slug', $slug)->where('id', '!=', $id)->where('grupo_id', $grupoId)->exists()) {
            $slug = Str::slug($validated['nome']).'-'.$contador;
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
            'ativo' => $request->ativo ?? true,
            'foto_principal' => $request->foto_principal ?? '',
            'fotos_galeria' => $request->fotos_galeria ?? [],
        ]);

        // Handle variations - DELETAR TODOS E RECRIAR
        $tipo = $validated['tipo'];

        if ($tipo === 'variacao') {
            // Delete ALL existing variations (children)
            $product->variations()->delete();

            // Create new variations if provided
            if ($request->has('variations') && $request->variations) {
                $varCounter = 1;
                foreach ($request->variations as $var) {
                    $varNome = $var['nome'] ?? 'Variação '.$varCounter;
                    $varSlug = $slug.'-'.Str::slug($varNome);
                    $varSku = $var['sku'] ?? $request->sku.'-'.$varCounter;
                    $herdar = $var['herdar'] ?? true;

                    while (Product::where('slug', $varSlug)->where('id', '!=', $product->id)->where('grupo_id', $grupoId)->exists()) {
                        $varSlug = $slug.'-'.Str::slug($varNome).'-'.$varCounter;
                        $varCounter++;
                    }

                    $varData = [
                        'empresa_id' => $product->empresa_id,
                        'grupo_id' => $product->grupo_id, // Herdar grupo_id do pai
                        'parent_id' => $product->id,
                        'nome' => $product->nome.' - '.$varNome,
                        'slug' => $varSlug,
                        'sku' => $varSku,
                        'tipo' => 'simples',
                        'variation_color' => $var['color'] ?? null,
                        'variation_size' => $var['size'] ?? null,
                        'herdar' => $herdar,
                        'ativo' => true,
                    ];

                    if (! $herdar) {
                        $varData['preco_venda'] = $var['preco_venda'] ?? $product->preco_venda;
                        $varData['preco_custo'] = $var['preco_custo'] ?? $product->preco_custo;
                        $varData['marca'] = $var['marca'] ?? $product->marca;
                        $varData['ncm'] = $var['ncm'] ?? $product->ncm;
                    } else {
                        $varData['preco_venda'] = $product->preco_venda;
                        $varData['preco_custo'] = $product->preco_custo;
                        $varData['marca'] = $product->marca;
                        $varData['ncm'] = $product->ncm;
                    }

                    Product::create($varData);
                    $varCounter++;
                }
            }
        }

        // Handle composto
        if ($tipo === 'composto') {
            // Delete existing components first
            $product->components()->delete();

            if ($request->has('components') && $request->components) {
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

        // Handle additional SKUs
        if ($request->has('skus') && $request->skus) {
            // Delete existing skus
            $product->skus()->delete();

            foreach ($request->skus as $sku) {
                if (! empty($sku['sku'])) {
                    $product->skus()->create([
                        'sku' => $sku['sku'],
                        'label' => $sku['label'] ?? null,
                        'gtin' => $sku['gtin'] ?? null,
                        'is_principal' => $sku['is_principal'] ?? false,
                        'grupo_id' => $grupoId,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'product' => $product->load('skus', 'variations', 'components.componentProduct'),
            'message' => 'Produto atualizado com sucesso!',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $empresaId = session('empresa_id', 6);
        $empresa = \App\Models\Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        $product = Product::where('grupo_id', $grupoId)->findOrFail($id);

        $product->variations()->delete();
        $product->components()->delete();
        $product->skus()->delete();
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produto excluído com sucesso!',
        ]);
    }

    public function search(Request $request)
    {
        $empresaId = session('empresa_id', 6);
        $empresa = \App\Models\Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        $products = Product::where('grupo_id', $grupoId)
            ->where('nome', 'ilike', '%'.$request->q.'%')
            ->orWhere('sku', 'ilike', '%'.$request->q.'%')
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

    public function export(Request $request)
    {
        $empresaId = $request->get('empresa', session('empresa_id', 6));

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductsExport($empresaId),
            'produtos_'.date('d_m_Y_H_i').'.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $empresaId = $request->get('empresa', session('empresa_id', 6));

        try {
            \Maatwebsite\Excel\Facades\Excel::import(
                new \App\Imports\ProductsImport($empresaId),
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' => 'Importação concluída com sucesso!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na importação: '.$e->getMessage(),
            ], 422);
        }
    }
}
