<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAnuncio;
use App\Services\MeliIntegrationService;
use Illuminate\Console\Command;

class DebugMeli extends Command
{
    protected $signature = 'debug:meli {anuncio_id?}';
    protected $description = 'Debug Meli Data';

    public function handle()
    {
        $anuncioId = $this->argument('anuncio_id');
        
        $query = MarketplaceAnuncio::where('marketplace', 'mercadolivre');
        if ($anuncioId) {
            $query->where('id', $anuncioId);
        } else {
            // Check for inconsistencies first
            $inconsistent = MarketplaceAnuncio::where('marketplace', 'mercadolivre')
                ->where('json_data->catalog_listing', true)
                ->whereNull('json_data->catalog_product_id')
                ->count();
            
            $this->info("Inconsistent Catalog Listings (True but no ID): $inconsistent");

            $query->limit(5);
        }
        
        $anuncios = $query->get();

        foreach ($anuncios as $anuncio) {
            $this->info("Anúncio: {$anuncio->titulo} ({$anuncio->external_id})");
            $data = $anuncio->json_data ?? [];
            
            $this->info("  - Catalog Product ID: " . ($data['catalog_product_id'] ?? 'N/A'));
            $this->info("  - Catalog Listing: " . ($data['catalog_listing'] ?? 'N/A'));
            $this->info("  - Listing Type: " . ($data['listing_type_id'] ?? 'N/A'));
            
            // Re-fetch item to check if json_data is stale
            try {
                 $meliService = new MeliIntegrationService($anuncio->empresa_id);
                 $item = $meliService->getItem($anuncio->external_id);
                 if ($item) {
                     $this->info("  - API Catalog Product ID: " . ($item['catalog_product_id'] ?? 'N/A'));
                 }
            } catch (\Exception $e) {
                 $this->error("  - API Error: " . $e->getMessage());
            }

            $this->newLine();
        }
    }
}
