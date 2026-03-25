<?php

namespace App\Services;

use App\Models\AmazonAdsConfig;
use Illuminate\Support\Facades\Http;
use Exception;

class AmazonAdsApiService
{
    protected AmazonAdsAuthService $authService;
    protected \Illuminate\Http\Client\PendingRequest $client;
    protected AmazonAdsConfig $config;

    public function __construct(AmazonAdsConfig $config)
    {
        $this->authService = new AmazonAdsAuthService();
        
        // Ensure token is fresh before starting API calls.
        // In reality, you'd check expiration or just catch 401s and retry.
        // We will do a generic approach: if no token, try refreshing.
        if (!$config->access_token) {
            $this->authService->refreshAccessToken($config);
            $config->refresh(); // reload from db
        }

        $this->config = $config;
        $this->client = $this->authService->getHttpClient($config);
    }

    /**
     * Re-initializes client after token refresh
     */
    private function reauthenticate() {
        $this->authService->refreshAccessToken($this->config);
        $this->config->refresh();
        $this->client = $this->authService->getHttpClient($this->config);
    }

    /**
     * Dynamic HTTP call with Auto-Retry on 401 Unauthorized
     */
    protected function request(string $method, string $uri, array $payload = [], string $acceptHeader = 'application/vnd.spCampaign.v3+json', $version = 'v3')
    {
        $this->client->withHeaders([
            'Accept' => $acceptHeader,
            'Content-Type' => 'application/vnd.spCampaign.v3+json' // Defaulting to v3 payloads usually
        ]);

        $fullUri = "/sp/{$uri}"; // Sponsored Products endpoint namespace usually

        $response = $this->client->{$method}($fullUri, $payload);

        if ($response->status() === 401) {
            $this->reauthenticate();
            // Re-apply headers just in case
            $this->client->withHeaders([
                'Accept' => $acceptHeader,
                'Content-Type' => 'application/vnd.spCampaign.v3+json'
            ]);
            $response = $this->client->{$method}($fullUri, $payload);
        }

        if ($response->failed()) {
            throw new Exception("Amazon Ads API Error ({$response->status()}): " . $response->body());
        }

        return $response->json();
    }

    /**
     * Creates an SP Campaign
     */
    public function createCampaign(string $name, float $dailyBudget, string $biddingStrategy, string $targetingType = 'MANUAL')
    {
        $payload = [
            'campaigns' => [
                [
                    'name' => $name,
                    'targetingType' => $targetingType,
                    'state' => 'ENABLED',
                    'dynamicBidding' => [
                        'strategy' => $biddingStrategy // LEGACY_FOR_SALES (down only), AUTO_FOR_SALES (up and down)
                    ],
                    'budget' => [
                        'budgetType' => 'DAILY',
                        'budget' => $dailyBudget
                    ]
                ]
            ]
        ];

        return $this->request('post', 'campaigns', $payload);
    }

    /**
     * Creates an SP AdGroup
     */
    public function createAdGroup(string $campaignId, string $name, float $defaultBid)
    {
        $payload = [
            'adGroups' => [
                [
                    'campaignId' => $campaignId,
                    'name' => $name,
                    'state' => 'ENABLED',
                    'defaultBid' => $defaultBid
                ]
            ]
        ];

        return $this->request('post', 'adGroups', $payload);
    }

    /**
     * Associates a Product (SKU) to an AdGroup
     */
    public function createProductAd(string $campaignId, string $adGroupId, string $sku)
    {
        $payload = [
            'productAds' => [
                [
                    'campaignId' => $campaignId,
                    'adGroupId' => $adGroupId,
                    'sku' => $sku,
                    'state' => 'ENABLED'
                ]
            ]
        ];

        return $this->request('post', 'productAds', $payload, 'application/vnd.spProductAd.v3+json');
    }

    /**
     * Creates Keyword Targets
     */
    public function createKeywordTargets(string $campaignId, string $adGroupId, array $keywords, float $bid, string $matchType = 'EXACT')
    {
        $keywordObjects = array_map(function ($kw) use ($campaignId, $adGroupId, $bid, $matchType) {
            return [
                'campaignId' => $campaignId,
                'adGroupId' => $adGroupId,
                'state' => 'ENABLED',
                'keywordText' => $kw,
                'matchType' => $matchType,
                'bid' => $bid
            ];
        }, $keywords);

        $payload = ['keywords' => $keywordObjects];

        return $this->request('post', 'keywords', $payload, 'application/vnd.spKeyword.v3+json');
    }

    /**
     * Lists SP Campaigns
     * @link https://advertising.amazon.com/API/docs/en-us/sponsored-products/2-0/openapi/campaigns#/Campaigns/listCampaigns
     */
    public function getCampaigns(array $params = [])
    {
        $queryString = http_build_query($params);
        $uri = "campaigns" . ($queryString ? "?{$queryString}" : "");
        
        return $this->request('get', $uri);
    }

    /**
     * Creates Category or ASIN Targets (Product Targeting)
     */
    public function createProductTargets(string $campaignId, string $adGroupId, array $targets, float $bid, string $type = 'CATEGORY')
    {
        $targetObjects = array_map(function ($target) use ($campaignId, $adGroupId, $bid, $type) {
            
            $expression = [];
            if ($type === 'CATEGORY') {
                $expression = [['type' => 'ASIN_CATEGORY_SAME_AS', 'value' => $target]];
            } else if ($type === 'ASIN') {
                $expression = [['type' => 'ASIN_SAME_AS', 'value' => $target]];
            }

            return [
                'campaignId' => $campaignId,
                'adGroupId' => $adGroupId,
                'state' => 'ENABLED',
                'bid' => $bid,
                'expression' => $expression,
                'expressionType' => 'MANUAL'
            ];
        }, $targets);

        $payload = ['targets' => $targetObjects];

        return $this->request('post', 'targets', $payload, 'application/vnd.spTargetingClause.v3+json');
    }
}
