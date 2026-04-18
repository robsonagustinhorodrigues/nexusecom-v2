<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AmazonAdsConfig;
use App\Models\AmazonAdsMetric;
use App\Services\AmazonAdsApiService;
use Carbon\Carbon;
use Exception;

class AmazonAdsSyncMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $empresaId;
    public $date;

    public function __construct($empresaId, $date = null)
    {
        $this->empresaId = $empresaId;
        $this->date = $date ?? Carbon::yesterday()->format('Y-m-d');
    }

    public function handle(): void
    {
        $config = AmazonAdsConfig::where('empresa_id', $this->empresaId)->where('is_active', true)->first();
        if (!$config) {
            return;
        }

        $apiService = new AmazonAdsApiService($config);

        // This is a simplified representation of the V3 Reporting API flow.
        // In production, you POST to /reporting/reports, poll for SUCCESS, then download the JSON/GZIP.
        try {
            // Pseudo-code for downloading the report array:
            // $reportId = $apiService->requestReport('sp', 'campaigns', $this->date);
            // $reportData = $apiService->downloadReport($reportId);
            $reportData = []; // Replace with actual downloaded data
            
            foreach ($reportData as $row) {
                // Determine ACOS safely
                $spend = $row['cost'] ?? 0;
                $sales = $row['sales'] ?? 0;
                $acos = $sales > 0 ? ($spend / $sales) * 100 : 0;

                AmazonAdsMetric::updateOrCreate(
                    [
                        'empresa_id' => $this->empresaId,
                        'date' => $this->date,
                        'entity_type' => 'campaign', // or keyword/target based on report type
                        'entity_id_amz' => $row['campaignId'],
                    ],
                    [
                        'impressions' => $row['impressions'] ?? 0,
                        'clicks' => $row['clicks'] ?? 0,
                        'spend' => $spend,
                        'sales' => $sales,
                        'orders' => $row['purchases'] ?? 0,
                        'acos' => $acos,
                    ]
                );
            }
        } catch (Exception $e) {
            \Log::error("Failed to sync metrics for empresa {$this->empresaId}: " . $e->getMessage());
        }
    }
}
