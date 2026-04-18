<?php

namespace App\Services;

use App\Models\AmazonAdsConfig;
use Illuminate\Support\Facades\Http;
use Exception;

class AmazonAdsAuthService
{
    /**
     * Refreshes the LWA Access Token using the Refresh Token.
     * Updates the config record in the database.
     */
    public function refreshAccessToken(AmazonAdsConfig $config): string
    {
        if (!$config->refresh_token || !$config->client_id || !$config->client_secret) {
            throw new Exception("LWA Credentials (Client ID, Secret, Refresh Token) are missing for Empresa {$config->empresa_id}.");
        }

        $response = Http::asForm()->post('https://api.amazon.com/auth/o2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $config->refresh_token,
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
        ]);

        if ($response->failed()) {
            throw new Exception("Failed to refresh Amazon Ads token: " . $response->body());
        }

        $data = $response->json();
        
        $config->update([
            'access_token' => $data['access_token'],
            // Amazon doesn't always return a new refresh token, only update if present
            'refresh_token' => $data['refresh_token'] ?? $config->refresh_token, 
        ]);

        return $data['access_token'];
    }

    /**
     * Returns a configured HTTP client pointing to the correct Ads API region.
     */
    public function getHttpClient(AmazonAdsConfig $config)
    {
        $endpoints = [
            'na' => 'https://advertising-api.amazon.com',
            'eu' => 'https://advertising-api-eu.amazon.com',
            'sa' => 'https://advertising-api.amazon.com', // SA uses NA endpoint usually
        ];

        $baseUrl = $endpoints[strtolower($config->region)] ?? $endpoints['na'];

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $config->access_token,
            'Amazon-Advertising-API-ClientId' => $config->client_id,
            'Amazon-Advertising-API-Scope' => $config->profile_id,
            'Content-Type' => 'application/json',
            'Accept' => 'application/vnd.spCampaign.v3+json', // Adjust Accept headers per endpoint in ApiService when needed
        ])->baseUrl($baseUrl);
    }
}
