<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAnuncio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnuncioController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->get('empresa_id', session('empresa_id', 6));
        
        $query = MarketplaceAnuncio::where('empresa_id', $empresaId);
        
        // Filters
        if ($request->marketplace) {
            $query->where('marketplace', $request->marketplace);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $anuncios = $query->orderBy('updated_at', 'desc')->get();
        
        return response()->json([
            'data' => $anuncios->map(function ($ad) {
                return $this->formatAnuncio($ad);
            })
        ]);
    }

    private function formatAnuncio($ad)
    {
        // Get product link info
        $productLinked = false;
        $productName = null;
        $productStock = null;
        $productCost = 0;

        if ($ad->productSku) {
            $productLinked = true;
            $productName = $ad->productSku->product?->nome ?? 'Produto vinculado';
            $productStock = $ad->productSku->product?->estoque ?? 0;
            $productCost = floatval($ad->productSku->preco_custo ?? $ad->productSku->product?->preco_custo ?? 0);
        }

        // Calculate profitability
        $lucro = $this->calcularLucratividade($ad);

        // Get dimensions
        $jsonData = is_array($ad->json_data) ? $ad->json_data : json_decode($ad->json_data, true) ?? [];
        $medidas = $this->getMedidas($jsonData);

        // Promotion info
        $hasPromotion = !empty($ad->promocao_valor);

        return [
            'id' => $ad->id,
            'external_id' => $ad->external_id,
            'title' => $ad->title,
            'sku' => $ad->sku,
            'price' => $ad->price,
            'original_price' => $ad->preco_original,
            'promocao_valor' => $ad->promocao_valor,
            'promocao_desconto' => $ad->promocao_desconto,
            'promocao_tipo' => $ad->promocao_tipo,
            'cost' => $productCost,
            'stock' => $ad->stock,
            'status' => $ad->status,
            'marketplace' => $ad->marketplace,
            'thumbnail' => $ad->thumbnail,
            'sold_quantity' => $ad->sold_quantity,
            'listing_type' => $jsonData['listing_type_id'] ?? 'gold_special',
            
            // Product link
            'product_linked' => $productLinked,
            'product_name' => $productName,
            'product_stock' => $productStock,
            
            // Profitability
            'lucro' => $lucro,
            
            // Dimensions
            'medidas' => $medidas,
            
            // Promotion
            'has_promotion' => $hasPromotion,
            
            // Repricer
            'repricer_active' => $ad->repricerConfig?->is_active ?? false,
            
            'created_at' => $ad->created_at,
            'updated_at' => $ad->updated_at,
        ];
    }

    private function calcularLucratividade($anuncio)
    {
        $preco = floatval($anuncio->preco ?? 0);
        
        // Get custo from product link
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
        
        if ($custoAdicional == 0 && isset($anuncio->custo_adicional)) {
            $custoAdicional = floatval($anuncio->custo_adicional);
        }

        $jsonData = is_array($anuncio->json_data) ? $anuncio->json_data : json_decode($anuncio->json_data, true) ?? [];
        $listingType = $jsonData['listing_type_id'] ?? 'gold_special';
        
        // Taxas baseadas no tipo de anúncio
        $taxaPercent = match($listingType) {
            'gold_special' => 0.16,
            'gold_pro' => 0.12,
            'silver' => 0.11,
            'bronze' => 0.10,
            'free' => 0.12,
            default => 0.12
        };
        
        // Frete
        $frete = floatval($anuncio->frete_custo_seller ?? 0);
        $freteGratis = $frete <= 0;
        $freteType = $anuncio->frete_type ?? 'standard';
        
        // Imposto (10% do preço)
        $imposto = $preco * 0.10;
        
        $valorTaxas = $preco * $taxaPercent;
        
        // Custo total = custo base + custo adicional
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
            'frete_type' => $freteType,
            'imposto' => $imposto,
            'lucro_bruto' => $lucroBruto,
            'margem' => $margem,
        ];
    }

    private function getMedidas($jsonData)
    {
        $medidas = [];
        
        if (!empty($jsonData['height'])) {
            $medidas['altura'] = $jsonData['height'];
        }
        if (!empty($jsonData['width'])) {
            $medidas['largura'] = $jsonData['width'];
        }
        if (!empty($jsonData['length'])) {
            $medidas['comprimento'] = $jsonData['length'];
        }
        if (!empty($jsonData['weight'])) {
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
        
        $ad->update($request->only(['status', 'price', 'stock', 'title']));
        
        return response()->json(['success' => true, 'message' => 'Anúncio atualizado']);
    }
    
    public function sync(Request $request)
    {
        // Placeholder for sync functionality
        return response()->json([
            'success' => true, 
            'message' => 'Sincronização iniciada',
            'synced' => 0
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
            ->map(fn($p) => [
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
