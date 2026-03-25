<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AmazonAdsConfig;
use App\Models\MarketplaceAnuncio;
use App\Models\AmazonAdsCampaign;
use App\Models\AmazonAdsMetric;
use App\Models\AmazonAdsLog;
use App\Services\AmazonAdsApiService;
use Carbon\Carbon;

class AmazonAdsBudgetScalerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $empresaId;

    public function __construct($empresaId)
    {
        $this->empresaId = $empresaId;
    }

    public function handle(): void
    {
        $globalConfig = AmazonAdsConfig::where('empresa_id', $this->empresaId)->where('is_active', true)->first();
        if (!$globalConfig) {
            return;
        }

        $apiService = new AmazonAdsApiService($globalConfig);
        $dateLimit30Days = Carbon::now()->subDays(30)->format('Y-m-d');

        $campaigns = AmazonAdsCampaign::where('empresa_id', $this->empresaId)
            ->where('state', 'enabled')
            ->get();

        foreach ($campaigns as $campaign) {
            $metricsData = AmazonAdsMetric::where('empresa_id', $this->empresaId)
                ->where('entity_id_amz', $campaign->campaign_id_amz)
                ->where('date', '>=', $dateLimit30Days)
                ->selectRaw('SUM(spend) as total_spend, SUM(sales) as total_sales')
                ->first();

            $totalSpend = $metricsData->total_spend ?? 0;
            $totalSales = $metricsData->total_sales ?? 0;
            $acos = $totalSales > 0 ? ($totalSpend / $totalSales) * 100 : 0;

            $oldBudget = $campaign->daily_budget;
            $newBudget = $oldBudget;

            // Escalonador de Orçamento
            if ($acos >= 0 && $acos <= 10 && $totalSales > 0) {
                // Se ACOS 0% a 10%: Aumentar orçamento em 100%.
                $newBudget = $oldBudget * 2;
                $this->updateCampaignBudget($apiService, $campaign, $oldBudget, $newBudget, "ACOS {$acos}% (0-10%). Aumentar orçamento em 100%.");
            } elseif ($acos > 10 && $acos <= 20) {
                // Se ACOS 10% a 20%: Aumentar orçamento em 50%.
                $newBudget = $oldBudget * 1.5;
                $this->updateCampaignBudget($apiService, $campaign, $oldBudget, $newBudget, "ACOS {$acos}% (10-20%). Aumentar orçamento em 50%.");
            } elseif ($acos > 30) {
                // Se ACOS > 30%: Reduzir orçamento em 50%.
                $newBudget = max(10, $oldBudget * 0.5); // Min budget assumption 10
                $this->updateCampaignBudget($apiService, $campaign, $oldBudget, $newBudget, "ACOS {$acos}% (>30%). Reduzir orçamento em 50%.");
            }

            // Campanhas Travadas (Criada > 30 dias, Gasto < R$ 5,00, tem Estoque)
            // Lets assume created_at > 30 days is true if it was inserted locally 30 days ago
            if ($campaign->created_at <= Carbon::now()->subDays(30) && $totalSpend < 5.00) {
                // Has stock?
                $anuncio = MarketplaceAnuncio::where('empresa_id', $this->empresaId)->where('sku', $campaign->sku)->first();
                if ($anuncio && $anuncio->estoque > 0) {
                    // Aumentar o lance padrao em 25% (AdGroup level or Targets)
                    $adGroups = $campaign->adGroups;
                    foreach ($adGroups as $adGroup) {
                        $oldBid = $adGroup->default_bid;
                        $newBid = $oldBid * 1.25;
                        try {
                            $apiService->request('put', 'adGroups', [
                                'adGroups' => [['adGroupId' => $adGroup->ad_group_id_amz, 'defaultBid' => $newBid]]
                            ]);
                            $adGroup->update(['default_bid' => $newBid]);
                            AmazonAdsLog::create([
                                'empresa_id' => $this->empresaId,
                                'action' => 'increase_bid',
                                'entity_type' => 'ad_group',
                                'entity_id_amz' => $adGroup->ad_group_id_amz,
                                'sku' => $campaign->sku,
                                'old_value' => $oldBid,
                                'new_value' => $newBid,
                                'reason' => "Campanha Travada: +30 dias, Gasto < R$ 5,00 ({$totalSpend}) com estoque físico."
                            ]);
                        } catch (\Exception $e) {}
                    }
                }
            }
        }
    }

    private function updateCampaignBudget($apiService, $campaign, $oldBudget, $newBudget, $reason)
    {
        if ($oldBudget == $newBudget) return;

        try {
            $apiService->request('put', 'campaigns', [
                'campaigns' => [
                    [
                        'campaignId' => $campaign->campaign_id_amz,
                        'budget' => ['budgetType' => 'DAILY', 'budget' => $newBudget]
                    ]
                ]
            ]);

            $campaign->update(['daily_budget' => $newBudget]);

            AmazonAdsLog::create([
                'empresa_id' => $this->empresaId,
                'action' => 'update_budget',
                'entity_type' => 'campaign',
                'entity_id_amz' => $campaign->campaign_id_amz,
                'sku' => $campaign->sku,
                'old_value' => $oldBudget,
                'new_value' => $newBudget,
                'reason' => $reason
            ]);
        } catch (\Exception $e) {}
    }
}
