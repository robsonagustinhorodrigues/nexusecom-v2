<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAnuncio;
use App\Services\MeliIntegrationService;
use Illuminate\Http\Request;

class AnuncioController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->get('empresa', $request->get('empresa_id', session('empresa_id', 6)));

        $query = MarketplaceAnuncio::where('empresa_id', $empresaId)
            ->whereNull('closed_at') // Excluir anúncios fechados/excluídos do ML
            ->with(['product', 'productSku', 'productSku.product', 'repricerConfig']);

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

        if ($request->repricer === 'ativo') {
            $query->whereHas('repricerConfig', function ($q) {
                $q->where('is_active', true);
            });
        } elseif ($request->repricer === 'inativo') {
            $query->whereDoesntHave('repricerConfig', function ($q) {
                $q->where('is_active', true);
            });
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

        $dateCreated = null;
        $lastUpdated = null;
        if (!empty($jsonData['date_created'])) {
            try {
                $dateCreated = \Carbon\Carbon::parse($jsonData['date_created'])->format('d/m/Y');
            } catch (\Exception $e) {}
        }
        if (!empty($jsonData['last_updated'])) {
            try {
                $lastUpdated = \Carbon\Carbon::parse($jsonData['last_updated'])->format('d/m/Y');
            } catch (\Exception $e) {}
        }

        $variationId = $jsonData['variation_id'] ?? null;
        if (empty($variationId) && !empty($jsonData['variations']) && is_array($jsonData['variations'])) {
            $count = count($jsonData['variations']);
            if ($count === 1) {
                $variationId = $jsonData['variations'][0]['id'] ?? null;
            } else {
                $variationId = "{$count} Vars";
            }
        }

        return [
            'id' => $ad->id,
            'external_id' => $ad->external_id,
            'variation_id' => $variationId,
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
            'sold_quantity' => intval($jsonData['sold_quantity'] ?? 0),
            'visits' => intval($jsonData['visits'] ?? 0),
            'product_linked' => ! empty($ad->product_sku_id),
            'produto_id' => $ad->produto_id,
            'product_sku' => $ad->productSku?->sku ?? $ad->productSku?->product?->sku,
            'product_nome' => $ad->productSku?->product?->nome,
            'listing_type' => $jsonData['listing_type_id'] ?? 'gold_special',
            'custo' => floatval($lucroData['custo_total'] ?? 0),
            'custo_base' => floatval($lucroData['custo'] ?? 0),
            'custo_adicional' => floatval($lucroData['custo_adicional'] ?? 0),
            'lucro' => floatval($lucroData['lucro_bruto'] ?? 0),
            'margem' => floatval($lucroData['margem'] ?? 0),
            'taxas' => floatval($lucroData['taxas'] ?? 0),
            'taxa_percent' => floatval($lucroData['taxa_percent'] ?? 0),
            'imposto' => floatval($lucroData['imposto'] ?? 0),
            'imposto_percent' => floatval($lucroData['imposto_percent'] ?? 0),
            'frete' => floatval($lucroData['frete'] ?? 0),
            'frete_gratis' => $lucroData['frete_gratis'] ?? false,
            'medidas' => $medidas,
            'is_catalog' => $isCatalog,
            'has_promotion' => $hasPromotion,
            'repricer_active' => $ad->repricerConfig?->is_active ?? false,
            'json_data' => $jsonData,
            'created_at' => $ad->created_at,
            'updated_at' => $ad->updated_at,
            'meli_date_created' => $dateCreated,
            'meli_last_updated' => $lastUpdated,
            'slug' => \Illuminate\Support\Str::slug($ad->titulo),
        ];
    }

    private function calcularLucratividade($anuncio)
    {
        $preco = floatval($anuncio->preco ?? 0);

        $custo = 0;
        $custoAdicional = 0;

        // Tenta buscar do produto vinculado diretamente
        if ($anuncio->product) {
            $custo = floatval($anuncio->product->preco_custo ?? 0);
            $custoAdicional = floatval($anuncio->product->custo_adicional ?? 0);
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

        // Buscar imposto da empresa
        $empresa = \App\Models\Empresa::find($anuncio->empresa_id);
        $impostoPercent = floatval($empresa?->aliquota_icms ?? 10) / 100;
        if ($impostoPercent <= 0) {
            $impostoPercent = 0.10; // Default 10%
        }

        $jsonData = is_array($anuncio->json_data) ? $anuncio->json_data : json_decode($anuncio->json_data, true) ?? [];
        $listingType = $jsonData['listing_type_id'] ?? 'gold_special';

        $taxaPercent = 0.12; 

        if ($anuncio->marketplace === 'mercadolivre') {
            $taxaPercent = match ($listingType) {
                'gold_special' => 0.16,
                'gold_pro' => 0.12,
                'silver' => 0.11,
                'bronze' => 0.10,
                'free' => 0.12,
                default => 0.12
            };
        } elseif ($anuncio->marketplace === 'amazon') {
            // Amazon standard commission for most home/furniture products is 15-16%
            // If the user specified 16% in their request, let's use it as a more realistic default for Amazon
            $taxaPercent = 0.16; 
        }

        $frete = floatval($anuncio->frete_custo_seller ?? 0);
        $freteGratis = $frete <= 0;

        $imposto = $preco * $impostoPercent;
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
            'imposto_percent' => $impostoPercent * 100,
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

        // Check root properties
        if (! empty($jsonData['height'])) {
            $medidas['altura'] = $jsonData['height'] . ' cm';
        }
        if (! empty($jsonData['width'])) {
            $medidas['largura'] = $jsonData['width'] . ' cm';
        }
        if (! empty($jsonData['length'])) {
            $medidas['comprimento'] = $jsonData['length'] . ' cm';
        }
        if (! empty($jsonData['weight'])) {
            $medidas['peso'] = $jsonData['weight'] . ' g';
        }

        // Check attributes (Meli pattern)
        if (!empty($jsonData['attributes']) && is_array($jsonData['attributes'])) {
            foreach ($jsonData['attributes'] as $attr) {
                if ($attr['id'] === 'PACKAGE_HEIGHT') {
                    $medidas['altura'] = $attr['value_name'];
                }
                if ($attr['id'] === 'PACKAGE_WIDTH') {
                    $medidas['largura'] = $attr['value_name'];
                }
                if ($attr['id'] === 'PACKAGE_LENGTH') {
                    $medidas['comprimento'] = $attr['value_name'];
                }
                if ($attr['id'] === 'PACKAGE_WEIGHT') {
                    $medidas['peso'] = $attr['value_name'];
                }
            }
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

        $data = $request->only(['status', 'preco', 'estoque', 'titulo', 'sku']);

        $meliSynced = false;
        $meliError = null;

        if (($request->filled('titulo') || $request->filled('sku')) && $ad->marketplace === 'mercadolivre') {
            try {
                $service = new MeliIntegrationService($ad->empresa_id);
                $meliData = [];

                if ($request->filled('titulo')) {
                    $meliData['title'] = $request->titulo;
                }
                if ($request->filled('sku')) {
                    $meliData['sku'] = $request->sku;
                }

                $meliResult = $service->atualizarAnuncio($ad->external_id, $meliData);
                $meliSynced = $meliResult['success'] ?? false;
                $meliError = $meliResult['error'] ?? null;
            } catch (\Exception $e) {
                $meliError = $e->getMessage();
            }
        }

        if ($request->filled('sku')) {
            $data['sku'] = $request->sku;
        }

        $ad->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Anúncio atualizado',
            'meli_synced' => $meliSynced,
            'meli_error' => $meliError,
        ]);
    }

    public function sync(Request $request)
    {
        $empresaId = $request->get('empresa', $request->get('empresa_id', session('empresa_id', 6)));
        $marketplace = $request->get('marketplace', 'mercadolivre');

        \Log::info("Iniciando sincronização de anúncios: Empresa {$empresaId}, Marketplace {$marketplace}");

        try {
            if ($marketplace === 'amazon') {
                $integracao = \App\Models\Integracao::where('empresa_id', $empresaId)
                    ->where('marketplace', 'amazon')
                    ->where('ativo', true)
                    ->first();

                if (!$integracao) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Integração Amazon não encontrada ou inativa para esta empresa.'
                    ], 422);
                }

                $service = new \App\Services\AmazonSpApiService($empresaId);
                $result = $service->syncListings();
                
                if (isset($result['error']) && $result['error'] === 'Credenciais não configuradas') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Credenciais Amazon não configuradas corretamente para esta empresa.'
                    ], 422);
                }

                return response()->json($result);
            }

            if ($marketplace === 'mercadolivre') {
                $integracao = \App\Models\Integracao::where('empresa_id', $empresaId)
                    ->where('marketplace', 'mercadolivre')
                    ->where('ativo', true)
                    ->first();

                if (!$integracao) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Integração Mercado Livre não encontrada ou inativa para esta empresa.'
                    ], 422);
                }

                $service = new \App\Services\MeliService();
                $syncedCount = $service->syncAnuncios($integracao);

                return response()->json([
                    'success' => true,
                    'message' => "Sincronização Mercado Livre concluída: {$syncedCount} anúncios atualizados.",
                    'synced' => $syncedCount,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Marketplace inválido para sincronização.'
            ], 400);

        } catch (\Exception $e) {
            \Log::error("Erro na sincronização ({$marketplace}): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchProducts(Request $request)
    {
        $empresaId = $request->get('empresa', session('empresa_id', 6));
        $empresa = \App\Models\Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;
        $query = $request->get('q', '');

        if (empty($query) || ! $grupoId) {
            return response()->json([]);
        }

        $products = \App\Models\Product::where('grupo_id', $grupoId)
            ->with(['skus', 'parent'])
            ->withCount('variations')
            ->where('tipo', '!=', 'variacao') // Excluir produtos pais (tipo variacao = pai)
            ->where(function ($q) use ($query) {
                $searchTerm = '%'.$query.'%';
                $q->where('nome', 'ilike', $searchTerm)
                    ->orWhere('sku', 'ilike', $searchTerm)
                    ->orWhereHas('skus', function ($sq) use ($searchTerm) {
                        $sq->where('sku', 'ilike', $searchTerm);
                    });
            })
            ->limit(20)
            ->get()
            ->map(function ($p) {
                $firstSku = $p->skus->first();

                // Verificar se é uma variação (filho)
                $isVariation = ! empty($p->parent_id);
                $parentName = null;
                if ($isVariation && $p->parent) {
                    $parentName = $p->parent->nome;
                }

                // Contar variaçõesfilhos
                $variationsCount = $p->variations_count ?? 0;

                return [
                    'id' => $p->id,
                    'nome' => $p->nome,
                    'sku' => $firstSku?->sku ?? $p->sku,
                    'preco_venda' => floatval($p->preco_venda),
                    'preco_custo' => floatval($firstSku?->preco_custo ?? $p->preco_custo ?? 0),
                    'estoque' => $p->skus->sum('estoque') ?? 0,
                    'is_variation' => $isVariation,
                    'parent_name' => $parentName,
                    'variation_color' => $p->variation_color,
                    'variation_size' => $p->variation_size,
                    'has_variations' => $variationsCount > 0,
                    'variations_count' => $variationsCount,
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

    public function desvincular(Request $request, $id)
    {
        $anuncio = MarketplaceAnuncio::findOrFail($id);

        $anuncio->update([
            'product_sku_id' => null,
            'produto_id' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Produto desvinculado com sucesso!']);
    }

    public function vincularPorSku(Request $request)
    {
        $empresaId = $request->get('empresa', session('empresa_id', 6));
        $empresa = \App\Models\Empresa::find($empresaId);
        $grupoId = $empresa?->grupo_id;

        if (! $grupoId) {
            return response()->json(['success' => false, 'message' => 'Empresa não encontrada'], 422);
        }

        // Buscar anúncios sem vínculo
        $anunciosSemVinculo = MarketplaceAnuncio::where('empresa_id', $empresaId)
            ->whereNull('product_sku_id')
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->get();

        $vinculados = 0;
        $naoEncontrados = [];

        foreach ($anunciosSemVinculo as $anuncio) {
            // Buscar produto pelo SKU no grupo
            $produto = \App\Models\Product::where('grupo_id', $grupoId)
                ->where('sku', $anuncio->sku)
                ->first();

            if ($produto) {
                // Criar ou buscar SKU
                $sku = $produto->skus()->first();
                if (! $sku) {
                    $sku = $produto->skus()->create([
                        'sku' => $produto->sku,
                        'label' => 'Padrão',
                        'preco_venda' => $produto->preco_venda,
                        'estoque' => 0,
                    ]);
                }

                $anuncio->update([
                    'product_sku_id' => $sku->id,
                    'produto_id' => $produto->id,
                ]);
                $vinculados++;
            } else {
                $naoEncontrados[] = $anuncio->sku;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$vinculados} produto(s) vinculado(s) por SKU",
            'vinculados' => $vinculados,
            'nao_encontrados' => count($naoEncontrados),
            'skus_nao_encontrados' => array_slice($naoEncontrados, 0, 10), // Primeiros 10
        ]);
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
            'filter_full_only' => $config?->filter_full_only ?? false,
            'filter_classic_only' => $config?->filter_classic_only ?? false,
            'filter_premium_only' => $config?->filter_premium_only ?? false,
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
            'filter_full_only' => 'boolean',
            'filter_classic_only' => 'boolean',
            'filter_premium_only' => 'boolean',
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

    public function getRepricerLogs($id)
    {
        $logs = \App\Models\RepricerLog::where('marketplace_anuncio_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'strategy' => $log->strategy,
                    'preco_anterior' => floatval($log->preco_anterior),
                    'preco_novo' => floatval($log->preco_novo),
                    'menor_concorrente' => floatval($log->menor_concorrente),
                    'margem_lucro' => floatval($log->margem_lucro),
                    'lucro_bruto' => floatval($log->lucro_bruto),
                    'status' => $log->status,
                    'mensagem' => $log->mensagem,
                    'created_at' => $log->created_at->format('d/m/Y H:i:s'),
                ];
            }),
        ]);
    }
}
