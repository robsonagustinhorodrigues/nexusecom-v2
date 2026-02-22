<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAnuncio;
use Illuminate\Http\Request;

class AnuncioController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->get('empresa', $request->get('empresa_id', session('empresa_id', 6)));

        $query = MarketplaceAnuncio::where('empresa_id', $empresaId)
            ->with(['produto', 'productSku', 'productSku.product', 'repricerConfig']);

        if ($request->marketplace) {
            $query->where('marketplace', $request->marketplace);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->tipo === 'catalogo') {
            $query->where('json_data->catalog_listing', true);
        } elseif ($request->tipo === 'normal') {
            $query->where(function ($q) {
                $q->whereNull('json_data->catalog_listing')
                    ->orWhere('json_data->catalog_listing', false);
            });
        }

        if ($request->vinculo === 'vinculado') {
            $query->whereNotNull('product_sku_id');
        } elseif ($request->vinculo === 'nao_vinculado') {
            $query->whereNull('product_sku_id');
        }

        if ($request->search) {
            $search = '%'.mb_strtolower($request->search, 'UTF-8').'%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(titulo) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(external_id) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$search]);
            });
        }

        $anuncios = $query->orderBy('updated_at', 'desc')->paginate(100);

        return response()->json([
            'data' => $anuncios->map(function ($ad) {
                return $this->formatAnuncio($ad);
            }),
            'current_page' => $anuncios->currentPage(),
            'last_page' => $anuncios->lastPage(),
            'total' => $anuncios->total(),
        ]);
    }

    private function formatAnuncio($ad)
    {
        $productCost = 0;

        if ($ad->productSku) {
            $productCost = floatval($ad->productSku->preco_custo ?? $ad->productSku->product?->preco_custo ?? 0);
        }

        $lucroData = $this->calcularLucratividade($ad);

        $jsonData = is_array($ad->json_data) ? $ad->json_data : json_decode($ad->json_data, true) ?? [];
        $medidas = $this->getMedidas($jsonData);
        $hasPromotion = ! empty($ad->promocao_valor);
        $isCatalog = ! empty($jsonData['catalog_listing']);

        return [
            'id' => $ad->id,
            'external_id' => $ad->external_id,
            'titulo' => $ad->titulo,
            'sku' => $ad->sku,
            'preco' => floatval($ad->preco ?? 0),
            'original_price' => floatval($ad->preco_original ?? 0),
            'promocao_valor' => floatval($ad->promocao_valor ?? 0),
            'promocao_desconto' => floatval($ad->promocao_desconto ?? 0),
            'custo' => $productCost,
            'estoque' => intval($ad->estoque ?? 0),
            'status' => $ad->status,
            'marketplace' => $ad->marketplace,
            'thumbnail' => $ad->thumbnail ?? ($jsonData['thumbnail'] ?? null),
            'url' => $ad->url ?? '#',
            'sold_quantity' => intval($ad->sold_quantity ?? 0),
            'listing_type' => $jsonData['listing_type_id'] ?? 'gold_special',
            'product_linked' => ! empty($ad->product_sku_id),
            'produto_id' => $ad->produto_id,
            'custo' => floatval($lucroData['custo_total'] ?? 0),
            'custo_base' => floatval($lucroData['custo'] ?? 0),
            'custo_adicional' => floatval($lucroData['custo_adicional'] ?? 0),
            'lucro' => floatval($lucroData['lucro_bruto'] ?? 0),
            'margem' => floatval($lucroData['margem'] ?? 0),
            'taxas' => floatval($lucroData['taxas'] ?? 0),
            'medidas' => $medidas,
            'is_catalog' => $isCatalog,
            'has_promotion' => $hasPromotion,
            'repricer_active' => $ad->repricerConfig?->is_active ?? false,
            'json_data' => $jsonData,
            'created_at' => $ad->created_at,
            'updated_at' => $ad->updated_at,
        ];
    }

    private function calcularLucratividade($anuncio)
    {
        $preco = floatval($anuncio->preco ?? 0);

        $custo = 0;
        $custoAdicional = 0;

        // Tenta buscar do produto vinculado diretamente
        if ($anuncio->produto) {
            $custo = floatval($anuncio->produto->preco_custo ?? 0);
            $custoAdicional = floatval($anuncio->produto->custo_adicional ?? 0);
        }
        // Se não tiver produto_id, tenta pelo sku
        elseif ($anuncio->productSku) {
            $custo = floatval($anuncio->productSku->preco_custo ?? 0);
            if ($anuncio->productSku->product) {
                $custoAdicional = floatval($anuncio->productSku->product->custo_adicional ?? 0);
            }
            if ($custo == 0 && $anuncio->productSku->product) {
                $custo = floatval($anuncio->productSku->product->preco_custo ?? 0);
                $custoAdicional = floatval($anuncio->productSku->product->custo_adicional ?? 0);
            }
        }

        if ($custo == 0 && isset($anuncio->preco_custo)) {
            $custo = floatval($anuncio->preco_custo);
        }

        // Custo total = custo base + custo adicional
        $custoTotal = $custo + $custoAdicional;

        $jsonData = is_array($anuncio->json_data) ? $anuncio->json_data : json_decode($anuncio->json_data, true) ?? [];
        $listingType = $jsonData['listing_type_id'] ?? 'gold_special';

        $taxaPercent = match ($listingType) {
            'gold_special' => 0.16,
            'gold_pro' => 0.12,
            'silver' => 0.11,
            'bronze' => 0.10,
            'free' => 0.12,
            default => 0.12
        };

        $frete = floatval($anuncio->frete_custo_seller ?? 0);
        $freteGratis = $frete <= 0;

        $imposto = $preco * 0.10;
        $valorTaxas = $preco * $taxaPercent;

        $lucroBruto = $preco - $custoTotal - $valorTaxas - $frete - $imposto;
        $margem = $preco > 0 ? ($lucroBruto / $preco) * 100 : 0;

        return [
            'preco' => $preco,
            'custo' => $custo,
            'custo_adicional' => $custoAdicional,
            'custo_total' => $custoTotal,
            'taxas' => $valorTaxas,
            'taxa_percent' => $taxaPercent * 100,
            'frete' => $frete,
            'frete_gratis' => $freteGratis,
            'imposto' => $imposto,
            'lucro_bruto' => $lucroBruto,
            'margem' => $margem,
        ];
    }

    private function getMedidas($jsonData)
    {
        $medidas = [];

        if (! empty($jsonData['height'])) {
            $medidas['altura'] = $jsonData['height'];
        }
        if (! empty($jsonData['width'])) {
            $medidas['largura'] = $jsonData['width'];
        }
        if (! empty($jsonData['length'])) {
            $medidas['comprimento'] = $jsonData['length'];
        }
        if (! empty($jsonData['weight'])) {
            $medidas['peso'] = $jsonData['weight'];
        }

        return $medidas;
    }

    public function show($id)
    {
        $ad = MarketplaceAnuncio::findOrFail($id);

        return response()->json($this->formatAnuncio($ad));
    }

    public function update(Request $request, $id)
    {
        $ad = MarketplaceAnuncio::findOrFail($id);
        $ad->update($request->only(['status', 'preco', 'estoque', 'titulo']));

        return response()->json(['success' => true, 'message' => 'Anúncio atualizado']);
    }

    public function sync(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Sincronização iniciada',
            'synced' => 0,
        ]);
    }

    public function searchProducts(Request $request)
    {
        $empresaId = $request->get('empresa', session('empresa_id', 6));
        $query = $request->get('q', '');

        $products = \App\Models\Product::where('empresa_id', $empresaId)
            ->where(function ($q) use ($query) {
                $q->where('nome', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function ($p) {
                $firstSku = $p->skus()->first();

                return [
                    'id' => $p->id,
                    'nome' => $p->nome,
                    'sku' => $firstSku?->sku ?? $p->sku,
                    'preco_venda' => floatval($p->preco_venda),
                    'preco_custo' => floatval($firstSku?->preco_custo ?? $p->preco_custo ?? 0),
                    'estoque' => $p->skus()->sum('estoque') ?? 0,
                ];
            });

        return response()->json($products);
    }

    public function vincular(Request $request, $id)
    {
        $anuncio = MarketplaceAnuncio::findOrFail($id);
        $produtoId = $request->get('produto_id');

        if (! $produtoId) {
            return response()->json(['success' => false, 'message' => 'Produto não informado'], 422);
        }

        $produto = \App\Models\Product::find($produtoId);
        if (! $produto) {
            return response()->json(['success' => false, 'message' => 'Produto não encontrado'], 404);
        }

        $sku = $produto->skus()->first();
        if (! $sku) {
            $sku = $produto->skus()->create([
                'sku' => $produto->id.'-default',
                'label' => 'Padrão',
                'preco_venda' => $produto->preco_venda,
                'estoque' => 0,
            ]);
        }

        $anuncio->update(['product_sku_id' => $sku->id]);

        return response()->json(['success' => true, 'message' => 'Produto vinculado com sucesso!']);
    }

    public function getRepricerConfig($id)
    {
        $anuncio = MarketplaceAnuncio::findOrFail($id);

        $config = \App\Models\AnuncioRepricerConfig::where('marketplace_anuncio_id', $id)->first();

        return response()->json([
            'id' => $config?->id,
            'strategy' => $config?->strategy ?? 'igualar_menor',
            'offset_value' => $config?->offset_value ?? 0,
            'min_profit_margin' => $config?->min_profit_margin ?? null,
            'min_profit_type' => $config?->min_profit_type ?? 'percent',
            'is_active' => $config?->is_active ?? false,
        ]);
    }

    public function saveRepricerConfig(Request $request, $id)
    {
        $anuncio = MarketplaceAnuncio::findOrFail($id);

        $validated = $request->validate([
            'strategy' => 'required|in:igualar_menor,valor_abaixo,valor_acima',
            'offset_value' => 'required|numeric',
            'min_profit_margin' => 'nullable|numeric',
            'min_profit_type' => 'nullable|in:percent,value',
            'is_active' => 'boolean',
        ]);

        \App\Models\AnuncioRepricerConfig::updateOrCreate(
            ['marketplace_anuncio_id' => $id],
            $validated
        );

        return response()->json(['success' => true, 'message' => 'Configuração salva!']);
    }

    public function importAsProduct(Request $request, $id)
    {
        $anuncio = MarketplaceAnuncio::findOrFail($id);

        if ($anuncio->product_sku_id) {
            return response()->json(['success' => false, 'message' => 'Este anúncio já está vinculado a um produto.'], 400);
        }

        $jsonData = is_array($anuncio->json_data) ? $anuncio->json_data : json_decode($anuncio->json_data, true) ?? [];

        $nome = $jsonData['title'] ?? $anuncio->titulo;
        $precoVenda = floatval($jsonData['price'] ?? $anuncio->preco);
        $estoque = intval($jsonData['available_quantity'] ?? $anuncio->estoque);

        $fotos = [];
        if (! empty($jsonData['pictures'])) {
            foreach ($jsonData['pictures'] as $pic) {
                $fotos[] = $pic['url'] ?? $pic['secure_url'] ?? '';
            }
            $fotos = array_filter($fotos);
        }

        $attributes = $jsonData['attributes'] ?? [];
        $ean = null;
        $ncm = null;
        $cest = null;
        $marca = null;
        $peso = null;
        $altura = null;
        $largura = null;
        $profundidade = null;
        $skuFromApi = $jsonData['seller_custom_field'] ?? null;

        foreach ($attributes as $attr) {
            $attrId = $attr['id'] ?? '';
            $attrValue = $attr['value_name'] ?? $attr['value_id'] ?? null;

            if ($attrValue) {
                if (in_array($attrId, ['GTIN', 'EAN'])) {
                    $ean = $attrValue;
                }
                if ($attrId === 'NCM') {
                    $ncm = $attrValue;
                }
                if ($attrId === 'CEST') {
                    $cest = $attrValue;
                }
                if ($attrId === 'BRAND') {
                    $marca = $attrValue;
                }
                if ($attrId === 'SELLER_SKU') {
                    $skuFromApi = $attrValue;
                }
                if ($attrId === 'PACKAGE_HEIGHT') {
                    $altura = floatval($attrValue);
                }
                if ($attrId === 'PACKAGE_WIDTH') {
                    $largura = floatval($attrValue);
                }
                if ($attrId === 'PACKAGE_LENGTH') {
                    $profundidade = floatval($attrValue);
                }
                if ($attrId === 'PACKAGE_WEIGHT') {
                    $peso = floatval($attrValue) / 1000;
                }
            }
        }

        $variacoes = $jsonData['variations'] ?? [];
        $tipoProduto = ! empty($variacoes) ? 'variacao' : 'simples';

        $slug = \Illuminate\Support\Str::slug($nome);
        $slugBase = $slug;
        $contador = 1;
        while (\App\Models\Product::where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$contador;
            $contador++;
        }

        $product = \App\Models\Product::create([
            'empresa_id' => $anuncio->empresa_id,
            'nome' => $nome,
            'slug' => $slug,
            'descricao' => $nome,
            'marca' => $marca,
            'preco_venda' => $precoVenda,
            'preco_custo' => 0,
            'peso' => floatval($peso ?? 0),
            'altura' => floatval($altura ?? 0),
            'largura' => floatval($largura ?? 0),
            'profundidade' => floatval($profundidade ?? 0),
            'ean' => $ean,
            'ncm' => $ncm,
            'cest' => $cest,
            'tipo' => $tipoProduto,
            'ativo' => $anuncio->status === 'active',
            'marketplace' => $anuncio->marketplace,
            'external_id' => $anuncio->external_id,
            'marketplace_url' => $jsonData['permalink'] ?? null,
            'condicao' => $jsonData['condition'] ?? 'new',
            'foto_principal' => $fotos[0] ?? null,
            'fotos_galeria' => count($fotos) > 1 ? json_encode(array_slice($fotos, 1)) : null,
        ]);

        if ($product) {
            if (! empty($variacoes)) {
                $firstSkuId = null;
                $varIndex = 0;
                foreach ($variacoes as $variacao) {
                    $varIndex++;
                    $skuVariacao = $variacao['seller_custom_field'] ?? $variacao['sku'] ?? 'VAR-'.$variacao['id'] ?? null;
                    if (! $skuVariacao) {
                        $skuVariacao = 'VAR-'.$product->id.'-'.$varIndex;
                    }
                    $precoVariacao = floatval($variacao['price'] ?? $precoVenda);
                    $estoqueVariacao = intval($variacao['available_quantity'] ?? $variacao['stock'] ?? 0);

                    $sku = \App\Models\ProductSku::create([
                        'product_id' => $product->id,
                        'sku' => $skuVariacao,
                        'preco_venda' => $precoVariacao,
                        'estoque' => $estoqueVariacao,
                        'gtin' => $variacao['gtin'] ?? null,
                    ]);

                    if ($firstSkuId === null) {
                        $firstSkuId = $sku->id;
                    }
                }
                if ($firstSkuId) {
                    $anuncio->update(['product_sku_id' => $firstSkuId, 'produto_id' => $product->id]);
                }
            } else {
                $skuSimples = $skuFromApi ?: $anuncio->sku ?: $anuncio->external_id;

                $productSku = \App\Models\ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => $skuSimples,
                    'preco_venda' => $anuncio->preco,
                    'estoque' => $anuncio->estoque,
                    'gtin' => $ean,
                    'ncm' => $ncm,
                ]);

                $anuncio->update(['product_sku_id' => $productSku->id, 'produto_id' => $product->id]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Produto criado com sucesso!',
            'product_id' => $product->id,
        ]);
    }
}
