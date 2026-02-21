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

        $query = MarketplaceAnuncio::where('empresa_id', $empresaId);

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
            'thumbnail' => $ad->thumbnail,
            'url' => $ad->url ?? '#',
            'sold_quantity' => intval($ad->sold_quantity ?? 0),
            'listing_type' => $jsonData['listing_type_id'] ?? 'gold_special',
            'product_linked' => ! empty($ad->product_sku_id),
            'lucro' => floatval($lucroData['lucro_bruto'] ?? 0),
            'margem' => floatval($lucroData['margem'] ?? 0),
            'taxas' => floatval($lucroData['taxas'] ?? 0),
            'medidas' => $medidas,
            'is_catalog' => $isCatalog,
            'has_promotion' => $hasPromotion,
            'repricer_active' => $ad->repricerConfig?->is_active ?? false,
            'created_at' => $ad->created_at,
            'updated_at' => $ad->updated_at,
        ];
    }

    private function calcularLucratividade($anuncio)
    {
        $preco = floatval($anuncio->preco ?? 0);

        $custo = 0;
        $custoAdicional = 0;

        if ($anuncio->productSku) {
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
        $custoTotal = $custo + $custoAdicional;

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
        $query = $request->get('q', '');

        $products = \App\Models\Product::where('empresa_id', session('empresa_id', 6))
            ->where('nome', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'sku' => $p->sku,
                'preco_venda' => $p->preco_venda,
                'preco_custo' => $p->preco_custo,
                'estoque' => $p->estoque,
            ]);

        return response()->json($products);
    }
}
