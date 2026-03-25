<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AmazonAdsConfig;
use App\Models\AmazonAdsSkuConfig;
use App\Models\MarketplaceAnuncio;
use App\Models\AmazonAdsCampaign;
use App\Models\AmazonAdsAdGroup;
use App\Models\AmazonAdsLog;
use App\Services\AmazonAdsApiService;
use Exception;

class AmazonAdsAutomakerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $empresaId;
    public $sku;

    public function __construct($empresaId, $sku)
    {
        $this->empresaId = $empresaId;
        $this->sku = $sku;
    }

    public function handle(): void
    {
        // 1. Load Configurations
        $globalConfig = AmazonAdsConfig::where('empresa_id', $this->empresaId)->where('is_active', true)->first();
        if (!$globalConfig) {
            return; // Ads Automator disabled globally
        }

        $skuConfig = AmazonAdsSkuConfig::where('empresa_id', $this->empresaId)
            ->where('sku', $this->sku)
            ->where('is_active', true)
            ->first();

        if (!$skuConfig) {
            return; // Robot disabled for this SKU
        }

        // 2. Fetch Listing info (to know price and fulfillment type if possible)
        // Note: For Amazon, logistics is usually FBA or DBA. We'll simplify to FBA as default if we can't determine.
        $anuncio = MarketplaceAnuncio::where('empresa_id', $this->empresaId)
            ->where('marketplace', 'amazon')
            ->where('sku', $this->sku)
            ->first();
            
        $price = $anuncio ? $anuncio->preco : 0;
        $logistics = 'FBA'; // Simulating FBA for now.
        
        $priceTier = $price <= 150 ? '<150' : '>150';
        $generalBid = $price <= 150 ? 0.60 : 1.00;

        // 3. Init Service
        $apiService = new AmazonAdsApiService($globalConfig);

        // -------------------------------------------------------------
        // (A) Campanha Automática Geral (Em grupo)
        // -------------------------------------------------------------
        $campaign1Name = "AUTO - Geral - {$logistics} {$priceTier}";
        
        // Check if Campaign 1 exists locally
        $campaign1 = AmazonAdsCampaign::where('empresa_id', $this->empresaId)->where('name', $campaign1Name)->first();
        
        if (!$campaign1) {
            $amzCampaign1 = $apiService->createCampaign($campaign1Name, 50.00, 'AUTO_FOR_SALES', 'AUTO');
            $c1Id = $amzCampaign1['campaigns'][0]['campaignId'] ?? null;
            if ($c1Id) {
                $campaign1 = AmazonAdsCampaign::create([
                    'empresa_id' => $this->empresaId,
                    'campaign_id_amz' => $c1Id,
                    'name' => $campaign1Name,
                    'type' => 'auto_general',
                    'daily_budget' => 50.00,
                    'bidding_strategy' => 'AUTO_FOR_SALES'
                ]);

                // Create AdGroup
                $amzAdGroup1 = $apiService->createAdGroup($c1Id, 'Default AdGroup', $generalBid);
                $ag1Id = $amzAdGroup1['adGroups'][0]['adGroupId'] ?? null;
                if ($ag1Id) {
                    AmazonAdsAdGroup::create([
                        'campaign_id' => $campaign1->id,
                        'ad_group_id_amz' => $ag1Id,
                        'name' => 'Default AdGroup',
                        'default_bid' => $generalBid
                    ]);
                }
            }
        }

        // Link SKU to Campaign 1
        $adGroup1 = $campaign1 ? $campaign1->adGroups()->first() : null;
        if ($campaign1 && $adGroup1) {
            $apiService->createProductAd($campaign1->campaign_id_amz, $adGroup1->ad_group_id_amz, $this->sku);
        }

        // -------------------------------------------------------------
        // Helper to create INDIVIDUAL Campaigns
        // -------------------------------------------------------------
        $createIndividualCampaign = function ($name, $type, $budget, $bid, $targetingType) use ($apiService) {
            $amzCamp = $apiService->createCampaign($name, $budget, 'AUTO_FOR_SALES', $targetingType);
            $cId = $amzCamp['campaigns'][0]['campaignId'] ?? null;
            if (!$cId) return null;

            $localCamp = AmazonAdsCampaign::create([
                'empresa_id' => $this->empresaId,
                'campaign_id_amz' => $cId,
                'sku' => $this->sku,
                'name' => $name,
                'type' => $type,
                'daily_budget' => $budget,
                'bidding_strategy' => 'AUTO_FOR_SALES'
            ]);

            $amzAg = $apiService->createAdGroup($cId, "AdGroup {$this->sku}", $bid);
            $agId = $amzAg['adGroups'][0]['adGroupId'] ?? null;
            
            if ($agId) {
                $localAg = AmazonAdsAdGroup::create([
                    'campaign_id' => $localCamp->id,
                    'ad_group_id_amz' => $agId,
                    'name' => "AdGroup {$this->sku}",
                    'default_bid' => $bid
                ]);

                // Always add the SKU to the AdGroup
                $apiService->createProductAd($cId, $agId, $this->sku);
                
                return ['campaign' => $localCamp, 'adGroup' => $localAg];
            }
            return null;
        };

        // -------------------------------------------------------------
        // (B) Campanha Automática Individual
        // -------------------------------------------------------------
        if (!AmazonAdsCampaign::where('empresa_id', $this->empresaId)->where('sku', $this->sku)->where('type', 'auto_individual')->exists()) {
            $createIndividualCampaign("AUTO - Individual - {$this->sku}", 'auto_individual', 10.00, 0.66, 'AUTO');
        }

        // -------------------------------------------------------------
        // (C) Campanha Manual - Palavra-Chave
        // -------------------------------------------------------------
        $kws = is_array($skuConfig->keywords) ? $skuConfig->keywords : [];
        if (count($kws) > 0 && !AmazonAdsCampaign::where('empresa_id', $this->empresaId)->where('sku', $this->sku)->where('type', 'manual_kw')->exists()) {
            $res = $createIndividualCampaign("MANUAL - KW - {$this->sku}", 'manual_kw', 10.00, 0.51, 'MANUAL');
            if ($res) {
                // Add EXACT and PHRASE targets
                $apiService->createKeywordTargets($res['campaign']->campaign_id_amz, $res['adGroup']->ad_group_id_amz, $kws, 0.51, 'EXACT');
                $apiService->createKeywordTargets($res['campaign']->campaign_id_amz, $res['adGroup']->ad_group_id_amz, $kws, 0.51, 'PHRASE');
            }
        }

        // -------------------------------------------------------------
        // (D) Campanha Manual - Categoria
        // -------------------------------------------------------------
        $cats = is_array($skuConfig->categories) ? $skuConfig->categories : [];
        if (count($cats) > 0 && !AmazonAdsCampaign::where('empresa_id', $this->empresaId)->where('sku', $this->sku)->where('type', 'manual_category')->exists()) {
            $res = $createIndividualCampaign("MANUAL - CAT - {$this->sku}", 'manual_category', 10.00, 0.51, 'MANUAL');
            if ($res) {
                $apiService->createProductTargets($res['campaign']->campaign_id_amz, $res['adGroup']->ad_group_id_amz, $cats, 0.51, 'CATEGORY');
            }
        }

        // -------------------------------------------------------------
        // (E) Campanha Manual - ASIN
        // -------------------------------------------------------------
        $asins = is_array($skuConfig->asins) ? $skuConfig->asins : [];
        if (count($asins) > 0 && !AmazonAdsCampaign::where('empresa_id', $this->empresaId)->where('sku', $this->sku)->where('type', 'manual_asin')->exists()) {
            $res = $createIndividualCampaign("MANUAL - ASIN - {$this->sku}", 'manual_asin', 10.00, 0.51, 'MANUAL');
            if ($res) {
                $apiService->createProductTargets($res['campaign']->campaign_id_amz, $res['adGroup']->ad_group_id_amz, $asins, 0.51, 'ASIN');
            }
        }

        // Log completion
        AmazonAdsLog::create([
            'empresa_id' => $this->empresaId,
            'action' => 'automaker_executed',
            'entity_type' => 'sku',
            'sku' => $this->sku,
            'reason' => 'Robô Automaker disparou criação das 5 campanhas para o SKU ' . $this->sku
        ]);
    }
}
