<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAnuncio;
use Illuminate\Console\Command;

class SyncMeliItens extends Command
{
    protected $signature = 'sync:meli-itens {empresaId? : ID da empresa}';

    protected $description = 'Sincroniza detalhes completos dos itens do Mercado Livre (SKU, descrição, atributos, etc)';

    public function handle()
    {
        $empresaId = $this->argument('empresaId');

        $query = MarketplaceAnuncio::where('marketplace', 'mercadolivre')
            ->whereNotNull('external_id');

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $total = $query->count();
        
        if ($total === 0) {
            $this->info("Nenhum anúncio do Mercado Livre encontrado.");
            return;
        }

        $this->info("Encontrados {$total} anúncios do Mercado Livre");
        
        $atualizados = 0;
        $erros = 0;

        $query->chunkById(50, function ($anuncios) use (&$atualizados, &$erros) {
            foreach ($anuncios as $anuncio) {
                try {
                    // Usa a integração correta para cada anúncio
                    $service = new MeliIntegrationService($anuncio->empresa_id);
                    
                    $this->line("Processando: {$anuncio->external_id}");
                    
                    $itemDetails = $service->getItemDetails($anuncio->external_id);
                    
                    if ($itemDetails) {
                        // Merge dos dados existentes com os novos detalhes
                        $existingData = is_array($anuncio->json_data) ? $anuncio->json_data : [];
                        
                        // Campos importantes para sincronizar
                        $fieldsToSync = [
                            'seller_custom_field',  // SKU
                            'description',          // Descrição
                            'attributes',           // Atributos (NCM, CEST, dimensões, peso)
                            'variations',           // Variações
                            'pictures',             // Fotos
                            'dimensions',           // Dimensões
                        ];
                        
                        $newData = array_merge($existingData, $itemDetails);
                        
                        $anuncio->update(['json_data' => $newData]);
                        
                        $atualizados++;
                        $this->info("  ✓ Atualizado: {$anuncio->external_id}");
                    } else {
                        $erros++;
                        $this->warn("  ✗ Falha ao buscar: {$anuncio->external_id}");
                    }
                    
                } catch (\Exception $e) {
                    $erros++;
                    $this->error("  ✗ Erro: {$anuncio->external_id} - " . $e->getMessage());
                }
            }
        });

        $this->info("========================================");
        $this->info("Sincronização concluída!");
        $this->info("Total processados: {$total}");
        $this->info("Atualizados: {$atualizados}");
        $this->info("Erros: {$erros}");
        
        return 0;
    }
}
