<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AmazonAdsConfig;
use App\Models\AmazonAdsSkuConfig;
use App\Models\MarketplaceAnuncio;
use App\Models\AmazonAdsCampaign;
use App\Models\AmazonAdsMetric;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Validator;

class AmazonAdsController extends Controller
{
    /**
     * Search for Amazon Listings
     */
    public function searchListings(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        $query = $request->get('q');

        if (!$empresaId) {
            return response()->json(['error' => 'Empresa ID is required'], 400);
        }

        $results = MarketplaceAnuncio::where('empresa_id', $empresaId)
            ->where('marketplace', 'amazon')
            ->when($query, function ($q) use ($query) {
                $q->where(function($sq) use ($query) {
                    $sq->where('titulo', 'ilike', "%{$query}%")
                      ->orWhere('sku', 'ilike', "%{$query}%")
                      ->orWhere('external_id', 'ilike', "%{$query}%");
                });
            })
            ->limit(20)
            ->get(['id', 'titulo', 'sku', 'external_id', 'json_data', 'thumbnail']);

        return response()->json($results);
    }

    /**
     * Generate AI Suggestions for an advertisement
     */
    public function generateAiSuggestions(Request $request, GeminiService $gemini)
    {
        $id = $request->get('anuncio_id');
        $anuncio = MarketplaceAnuncio::findOrFail($id);
        
        $config = AmazonAdsConfig::where('empresa_id', $anuncio->empresa_id)->first();
        $model = $config->gemini_model ?? 'gemini-1.5-flash';

        $jsonData = is_array($anuncio->json_data) ? $anuncio->json_data : json_decode($anuncio->json_data, true) ?? [];
        $description = $jsonData['item-description'] ?? $anuncio->titulo;

        $result = $gemini->generateAdsSuggestions($anuncio->titulo, $description, $model);

        return response()->json($result);
    }

    /**
     * Get Global Ads Configuration for the current Tenant
     */
    public function getConfig(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        
        if (!$empresaId) {
            return response()->json(['error' => 'Empresa ID is required'], 400);
        }

        $config = AmazonAdsConfig::firstOrCreate(
            ['empresa_id' => $empresaId],
            [
                'margem_alvo_padrao' => 20.00,
                'is_active' => false,
                'region' => 'na'
            ]
        );

        return response()->json($config);
    }

    /**
     * Save Global Ads Configuration
     */
    public function saveConfig(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        
        if (!$empresaId) {
            return response()->json(['error' => 'Empresa ID is required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'nullable|string',
            'client_secret' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'profile_id' => 'nullable|string',
            'region' => 'required|in:na,eu,sa',
            'margem_alvo_padrao' => 'required|numeric|min:0',
            'gemini_model' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $config = AmazonAdsConfig::updateOrCreate(
            ['empresa_id' => $empresaId],
            $request->only(['client_id', 'client_secret', 'refresh_token', 'profile_id', 'region', 'margem_alvo_padrao', 'gemini_model', 'is_active'])
        );

        return response()->json(['message' => 'Configurações salvas com sucesso', 'config' => $config]);
    }

    /**
     * Get SKU Configurations for Ads Automator
     */
    public function getSkuConfigs(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        
        if (!$empresaId) {
            return response()->json(['error' => 'Empresa ID is required'], 400);
        }

        // Ideally this joins with the actual Products table to list all active SKUs.
        // For now, returning the configurations that exist.
        $skuConfigs = AmazonAdsSkuConfig::with('anuncio')->where('empresa_id', $empresaId)->get();

        return response()->json($skuConfigs);
    }

    /**
     * Save SKU Configuration (Turn ON/OFF Robot, Configure KWs)
     */
    public function saveSkuConfig(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        
        if (!$empresaId) {
            return response()->json(['error' => 'Empresa ID is required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'sku' => 'required|string',
            'marketplace_anuncio_id' => 'nullable|exists:marketplace_anuncios,id',
            'is_active' => 'boolean',
            'margem_alvo' => 'nullable|numeric|min:0',
            'keywords' => 'nullable|array|max:10',
            'categories' => 'nullable|array|max:10',
            'asins' => 'nullable|array|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $skuConfig = AmazonAdsSkuConfig::where('empresa_id', $empresaId)->where('sku', $request->sku)->first();
        $wasActive = $skuConfig ? $skuConfig->is_active : false;

        $skuConfig = AmazonAdsSkuConfig::updateOrCreate(
            ['empresa_id' => $empresaId, 'sku' => $request->sku],
            $request->only(['marketplace_anuncio_id', 'is_active', 'margem_alvo', 'keywords', 'categories', 'asins'])
        );

        // If is_active changed from false to true, dispatch AmazonAdsAutomakerJob for this SKU
        if ($skuConfig->is_active && !$wasActive) {
            \App\Jobs\AmazonAdsAutomakerJob::dispatch($empresaId, $skuConfig->sku);
        }

        return response()->json(['message' => 'SKU configurado com sucesso', 'config' => $skuConfig]);
    }

    /**
     * Get Sync'd Campaigns from Database
     */
    public function listCampaigns(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        
        if (!$empresaId) {
            return response()->json(['error' => 'Empresa ID is required'], 400);
        }

        $campaigns = AmazonAdsCampaign::where('empresa_id', $empresaId)
            ->orderBy('name')
            ->get();

        // Aggregate Metrics for each campaign (Last 30 days)
        $campaigns->each(function($campaign) use ($empresaId) {
            $metrics = AmazonAdsMetric::where('empresa_id', $empresaId)
                ->where('entity_id_amz', $campaign->campaign_id_amz)
                ->where('entity_type', 'campaign')
                ->selectRaw('SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(spend) as spend, SUM(sales) as sales')
                ->first();

            $campaign->metrics = [
                'impressions' => (int) ($metrics->impressions ?? 0),
                'clicks' => (int) ($metrics->clicks ?? 0),
                'spend' => (float) ($metrics->spend ?? 0),
                'sales' => (float) ($metrics->sales ?? 0),
                'acos' => ($metrics->sales > 0) ? round(($metrics->spend / $metrics->sales) * 100, 2) : 0
            ];
        });

        return response()->json($campaigns);
    }

    /**
     * Pull Campaigns from Amazon Ads API to Local Database
     */
    public function syncCampaigns(Request $request)
    {
        $empresaId = $request->get('empresa_id');
        
        if (!$empresaId) {
            return response()->json(['error' => 'Empresa ID is required'], 400);
        }

        $config = AmazonAdsConfig::where('empresa_id', $empresaId)->first();
        if (!$config || !$config->is_active) {
            return response()->json(['error' => 'Configuração inativa'], 400);
        }

        try {
            \Log::info("Iniciando sincronização de campanhas Amazon para empresa {$empresaId}");
            $apiService = new \App\Services\AmazonAdsApiService($config);
            $amzCampaigns = $apiService->getCampaigns();
            
            \Log::info("Resposta da Amazon Ads API (Campaigns): " . json_encode($amzCampaigns));

            // Note: The structure might vary. Checking for 'campaigns' key.
            $campaignData = $amzCampaigns['campaigns'] ?? $amzCampaigns ?? [];
            
            $syncedCount = 0;
            foreach ($campaignData as $amzCampaign) {
                if (!isset($amzCampaign['campaignId'])) continue;

                AmazonAdsCampaign::updateOrCreate(
                    [
                        'empresa_id' => $empresaId,
                        'campaign_id_amz' => $amzCampaign['campaignId']
                    ],
                    [
                        'name' => $amzCampaign['name'],
                        'type' => $amzCampaign['targetingType'] ?? 'UNKNOWN',
                        'state' => $amzCampaign['state'],
                        'daily_budget' => $amzCampaign['budget']['budget'] ?? 0,
                    ]
                );
                $syncedCount++;
            }

            return response()->json(['message' => "{$syncedCount} campanhas sincronizadas com sucesso"]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
