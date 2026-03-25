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
use App\Models\AmazonAdsMetric;
use App\Models\AmazonAdsLog;
use App\Services\AmazonAdsApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AmazonAdsRulesEngineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $empresaId;
    public $daysWindow;

    public function __construct($empresaId, $daysWindow = 30)
    {
        $this->empresaId = $empresaId;
        $this->daysWindow = $daysWindow;
    }

    public function handle(): void
    {
        $globalConfig = AmazonAdsConfig::where('empresa_id', $this->empresaId)->where('is_active', true)->first();
        if (!$globalConfig) {
            return;
        }

        $apiService = new AmazonAdsApiService($globalConfig);
        $dateLimit = Carbon::now()->subDays($this->daysWindow)->format('Y-m-d');

        // We fetch SKU configs to know target margins and if robot is active
        $skuConfigs = AmazonAdsSkuConfig::where('empresa_id', $this->empresaId)
            ->where('is_active', true)
            ->get()
            ->keyBy('sku');

        $campaigns = AmazonAdsCampaign::where('empresa_id', $this->empresaId)
            ->where('state', 'enabled')
            ->get();

        foreach ($campaigns as $campaign) {
            $sku = $campaign->sku;
            $skuConfig = $skuConfigs->get($sku);
            
            // If the campaign has no SKU attached (e.g. some Auto General), we might loop its ad groups
            // For simplicity here, we assume we know the SKU or we are evaluating the campaign metrics directly.
            $targetMargin = $skuConfig?->margem_alvo ?? $globalConfig->margem_alvo_padrao;

            // Fetch metrics for this campaign in the window
            $metricsData = AmazonAdsMetric::where('empresa_id', $this->empresaId)
                ->where('entity_id_amz', $campaign->campaign_id_amz)
                ->where('date', '>=', $dateLimit)
                ->selectRaw('SUM(spend) as total_spend, SUM(sales) as total_sales, SUM(clicks) as total_clicks')
                ->first();

            $totalSpend = $metricsData->total_spend ?? 0;
            $totalSales = $metricsData->total_sales ?? 0;
            $totalClicks = $metricsData->total_clicks ?? 0;
            $acos = $totalSales > 0 ? ($totalSpend / $totalSales) * 100 : 0;

            // Mocking product price for calculations
            // Needs to find MarketplaceAnuncio for this SKU
            $anuncio = MarketplaceAnuncio::where('empresa_id', $this->empresaId)->where('sku', $sku)->first();
            $sellingPrice = $anuncio ? $anuncio->preco : 100; // Defaulting to 100 to avoid div by zero if missing

            // ------------------------------------------------------------------------------------------------
            // Regra A: Automática Geral
            // ------------------------------------------------------------------------------------------------
            if ($campaign->type === 'auto_general' && $sku) {
                if ($totalSpend > ($sellingPrice * 0.50)) {
                    if ($acos > $targetMargin) {
                        try {
                            // Pausar
                            $apiService->request('put', 'campaigns', [
                                'campaigns' => [['campaignId' => $campaign->campaign_id_amz, 'state' => 'PAUSED']]
                            ]);
                            $campaign->update(['state' => 'paused']);
                            $this->logAction('pause_campaign', 'campaign', $campaign->campaign_id_amz, $sku, 'ENABLED', 'PAUSED', "Regra A: Gasto > 50% ({$totalSpend}) e ACOS {$acos}% > Margem {$targetMargin}%");
                        } catch (\Exception $e) {}
                    }
                }
            }

            // ------------------------------------------------------------------------------------------------
            // Regra B: Automática Individual
            // ------------------------------------------------------------------------------------------------
            if ($campaign->type === 'auto_individual' && $sku) {
                if ($totalSpend > ($sellingPrice * 0.50)) {
                    if ($acos > 30) {
                        try {
                            // Pausar
                            $apiService->request('put', 'campaigns', [
                                'campaigns' => [['campaignId' => $campaign->campaign_id_amz, 'state' => 'PAUSED']]
                            ]);
                            $campaign->update(['state' => 'paused']);
                            $this->logAction('pause_campaign', 'campaign', $campaign->campaign_id_amz, $sku, 'ENABLED', 'PAUSED', "Regra B: Gasto > 50% e ACOS {$acos}% > 30%");
                        } catch (\Exception $e) {}
                    } elseif ($acos > 20 && $acos <= 30) {
                        // Reduce bid 50%
                        // Bids in Auto Individual are usually set at the AdGroup level
                        $adGroup = $campaign->adGroups()->first();
                        if ($adGroup) {
                            $newBid = max(0.02, $adGroup->default_bid * 0.50); // Minimum Amazon bid usually 0.02
                            try {
                                $apiService->request('put', 'adGroups', [
                                    'adGroups' => [['adGroupId' => $adGroup->ad_group_id_amz, 'defaultBid' => $newBid]]
                                ]);
                                $oldBid = $adGroup->default_bid;
                                $adGroup->update(['default_bid' => $newBid]);
                                $this->logAction('update_bid', 'ad_group', $adGroup->ad_group_id_amz, $sku, $oldBid, $newBid, "Regra B: ACOS {$acos}% entre 20% e 30%");
                            } catch (\Exception $e) {}
                        }
                    }
                }
            }

            // ------------------------------------------------------------------------------------------------
            // Regra C: Manuais (Palavra-chave / Categoria / ASIN)
            // ------------------------------------------------------------------------------------------------
            if (in_array($campaign->type, ['manual_kw', 'manual_category', 'manual_asin'])) {
                // Here we usually evaluate TARGETS (Keywords/ASINs) individually, not the campaign as a whole.
                // We'll simulate fetching targets for this campaign.
                $targets = \App\Models\AmazonAdsTarget::whereIn('ad_group_id', $campaign->adGroups()->pluck('id'))->where('state', 'enabled')->get();
                
                foreach ($targets as $target) {
                    $tMetrics = AmazonAdsMetric::where('empresa_id', $this->empresaId)
                        ->where('entity_id_amz', $target->target_id_amz)
                        ->where('date', '>=', $dateLimit)
                        ->selectRaw('SUM(spend) as total_spend, SUM(sales) as total_sales, SUM(clicks) as total_clicks')
                        ->first();
                    
                    $tClicks = $tMetrics->total_clicks ?? 0;
                    $tSpend = $tMetrics->total_spend ?? 0;
                    $tSales = $tMetrics->total_sales ?? 0;
                    $tAcos = $tSales > 0 ? ($tSpend / $tSales) * 100 : 0;

                    if ($tClicks >= 20) {
                        if ($tSales == 0 || $tAcos > 30) {
                            // Pausar Target
                            try {
                                $endpoint = $target->type === 'keyword' ? 'keywords' : 'targets';
                                $idKey = $target->type === 'keyword' ? 'keywordId' : 'targetId';
                                $apiService->request('put', $endpoint, [
                                    $endpoint => [[$idKey => $target->target_id_amz, 'state' => 'PAUSED']]
                                ]);
                                $target->update(['state' => 'paused']);
                                $this->logAction('pause_target', 'target', $target->target_id_amz, $sku, 'ENABLED', 'PAUSED', "Regra C: Cliques >= 20. ACOS {$tAcos}% > 30% ou Sem Vendas");
                            } catch (\Exception $e) {}
                        } elseif ($tAcos > 15 && $tAcos <= 30) {
                            // Reduce bid 50%
                            $newBid = max(0.02, $target->bid * 0.50);
                            try {
                                $endpoint = $target->type === 'keyword' ? 'keywords' : 'targets';
                                $idKey = $target->type === 'keyword' ? 'keywordId' : 'targetId';
                                $apiService->request('put', $endpoint, [
                                    $endpoint => [[$idKey => $target->target_id_amz, 'bid' => $newBid]]
                                ]);
                                $oldBid = $target->bid;
                                $target->update(['bid' => $newBid]);
                                $this->logAction('update_bid', 'target', $target->target_id_amz, $sku, $oldBid, $newBid, "Regra C: Cliques >= 20. ACOS {$tAcos}% entre 15% e 30%");
                            } catch (\Exception $e) {}
                        }
                    }
                }
            }
        }
    }

    private function logAction($action, $entityType, $entityId, $sku, $oldValue, $newValue, $reason)
    {
        AmazonAdsLog::create([
            'empresa_id' => $this->empresaId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id_amz' => $entityId,
            'sku' => $sku,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason
        ]);
    }
}
