<?php

namespace App\Console\Commands;

use App\Models\Integracao;
use App\Models\MarketplaceAnuncio;
use App\Services\MeliIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncFreteAnuncios extends Command
{
    protected $signature = 'sync:frete-anuncios {empresaId} {--marketplace=all : mercadolivre|amazon|all} {--limit=0 : Limite de anúncios (0 = todos)} {--force : Forçar atualização mesmo se já tem frete}';

    protected $description = 'Sincroniza custos de frete dos anúncios via API do marketplace';

    public function handle()
    {
        $empresaId = $this->argument('empresaId');
        $marketplace = $this->option('marketplace');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info("Sincronizando frete para empresa {$empresaId} (marketplace: {$marketplace})...");

        $total = 0;

        if ($marketplace === 'all' || $marketplace === 'mercadolivre') {
            $total += $this->syncMeli($empresaId, $limit, $force);
        }

        if ($marketplace === 'all' || $marketplace === 'amazon') {
            $total += $this->syncAmazon($empresaId, $limit, $force);
        }

        $this->info("Concluído! {$total} anúncios atualizados.");
        return 0;
    }

    private function syncMeli(int $empresaId, int $limit, bool $force): int
    {
        $integracao = Integracao::where('empresa_id', $empresaId)
            ->where('marketplace', 'mercadolivre')
            ->where('ativo', true)
            ->first();

        if (!$integracao) {
            $this->warn("Nenhuma integração ML ativa para empresa {$empresaId}");
            return 0;
        }

        $meliService = new MeliIntegrationService($empresaId);

        $query = MarketplaceAnuncio::where('empresa_id', $empresaId)
            ->where('marketplace', 'mercadolivre')
            ->whereNotNull('external_id');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('frete_custo_seller')
                  ->orWhere('frete_updated_at', '<', now()->subHours(24));
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $anuncios = $query->get();
        $this->info("  ML: {$anuncios->count()} anúncios para atualizar");

        $updated = 0;
        $bar = $this->output->createProgressBar($anuncios->count());
        $bar->start();

        foreach ($anuncios as $anuncio) {
            try {
                // 1. Buscar detalhes completos do item (para garantir dados frescos como catalog_product_id)
                $itemData = $meliService->getItem($anuncio->external_id);
                
                if ($itemData) {
                    // Atualizar json_data para garantir que temos catalog_product_id se existir
                    $anuncio->update(['json_data' => $itemData]);

                    // Chama a nova função de obter custo exato do logista
                    $resultadoCusto = $meliService->obterCustoFreteMercadoLivre($anuncio->external_id);
                    $cost = $resultadoCusto['cost'] ?? 0.0;
                    $source = $resultadoCusto['source'] ?? 'not_free';

                    $anuncio->update([
                        'frete_custo_seller' => $cost,
                        'frete_source' => $source,
                        'frete_updated_at' => now(),
                    ]);
                    $updated++;
                }

                // Rate limit: 200ms entre chamadas
                usleep(200000);
            } catch (\Exception $e) {
                // $this->warn("  ⚠ Erro {$anuncio->external_id}: " . $e->getMessage());
                Log::warning("SyncFrete ML error: {$anuncio->external_id}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  ML: {$updated} atualizados");
        return $updated;
    }

    private function syncAmazon(int $empresaId, int $limit, bool $force): int
    {
        $query = MarketplaceAnuncio::where('empresa_id', $empresaId)
            ->where('marketplace', 'amazon')
            ->whereNotNull('external_id');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('frete_custo_seller')
                  ->orWhere('frete_updated_at', '<', now()->subHours(24));
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $anuncios = $query->get();
        $this->info("  Amazon: {$anuncios->count()} anúncios para atualizar");

        $updated = 0;

        foreach ($anuncios as $anuncio) {
            $preco = $this->getAmazonPrice($anuncio);
            $freteFba = $this->calcularFreteFbaBrasil($preco);

            $anuncio->update([
                'frete_custo_seller' => $freteFba,
                'frete_source' => 'estimated',
                'frete_updated_at' => now(),
            ]);
            $updated++;
        }

        $this->info("  Amazon: {$updated} atualizados");
        return $updated;
    }

    private function getAmazonPrice(MarketplaceAnuncio $anuncio): float
    {
        $jsonData = $anuncio->json_data ?? [];

        if (isset($jsonData['ItemPrice']['Amount'])) {
            return (float) $jsonData['ItemPrice']['Amount'];
        }

        return (float) ($anuncio->preco ?? 0);
    }

    /**
     * Calcula taxa FBA Amazon Brasil:
     * - Preço >= R$100: frete grátis (absorvido pela Amazon)
     * - Preço < R$100: R$5 por unidade
     */
    private function calcularFreteFbaBrasil(float $preco): float
    {
        if ($preco >= 100) {
            return 0.00;
        }
        return 5.00;
    }

    /**
     * Estimativa local de frete ML baseada em faixas de peso
     */
    private function estimarFreteML(float $pesoGramas): float
    {
        $faixas = [
            ["max" => 300, "valor" => 8.90],
            ["max" => 500, "valor" => 10.90],
            ["max" => 1000, "valor" => 12.90],
            ["max" => 1500, "valor" => 14.90],
            ["max" => 2000, "valor" => 16.90],
            ["max" => 3000, "valor" => 19.90],
            ["max" => 5000, "valor" => 24.90],
            ["max" => 10000, "valor" => 34.90],
            ["max" => 15000, "valor" => 44.90],
            ["max" => 20000, "valor" => 54.90],
            ["max" => 25000, "valor" => 64.90],
            ["max" => 30000, "valor" => 74.90],
        ];
        
        foreach ($faixas as $faixa) {
            if ($pesoGramas <= $faixa["max"]) {
                return $faixa["valor"];
            }
        }
        
        $valorBase = 74.90;
        $pesoExcedente = ($pesoGramas - 30000) / 5000;
        return round($valorBase + ($pesoExcedente * 10), 2);
    }
}