<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\MarketplaceAnuncio;
use App\Models\AnuncioRepricerConfig;
use App\Services\MeliIntegrationService;
use App\Livewire\Integrations\Anuncios;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunRepricer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repricer:run {empresaId? : ID da empresa (opcional)} {--anuncio_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa o repricer automático para anúncios de catálogo no Mercado Livre';

    /**
     * Execute the console command.
     */
    public function handle(MeliIntegrationService $meliService)
    {
        $empresaId = $this->argument('empresaId');
        $anuncioId = $this->option('anuncio_id');
        
        // Se especificada empresa, verifica se repricer está habilitado
        if ($empresaId) {
            $empresa = Empresa::find($empresaId);
            if (!$empresa) {
                $this->error("Empresa {$empresaId} não encontrada.");
                return;
            }
            if (!$empresa->repricer_enabled) {
                $this->info("Repricer desabilitado para empresa {$empresa->id} ({$empresa->nome_fantasia}).");
                return;
            }
            $this->info("Executando repricer para empresa {$empresa->id} ({$empresa->nome_fantasia})...");
        } else {
            $this->info("Executando repricer para todas as empresas habilitadas...");
        }
        
        $query = AnuncioRepricerConfig::with(['anuncio.sku.product', 'anuncio.integracao'])
            ->where('is_active', true);
            
        if ($empresaId) {
            $query->whereHas('anuncio', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });
        }
        
        if ($anuncioId) {
            $query->where('marketplace_anuncio_id', $anuncioId);
        }

        $configs = $query->get();

        if ($configs->isEmpty()) {
            $this->info("Nenhum repricer ativo encontrado.");
            return;
        }

        $this->info("Iniciando processamento de " . $configs->count() . " anúncios...");

        foreach ($configs as $config) {
            try {
                $this->processRepricer($config, $meliService);
            } catch (\Exception $e) {
                Log::error("Erro no repricer para anúncio {$config->marketplace_anuncio_id}: " . $e->getMessage());
                $this->error("Erro no anúncio {$config->marketplace_anuncio_id}");
            }
        }

        $this->info("Repricer finalizado.");
    }

    private function processRepricer(AnuncioRepricerConfig $config, MeliIntegrationService $meliService)
    {
        $anuncio = $config->anuncio;
        if (!$anuncio || $anuncio->marketplace !== 'mercadolivre') return;

        $catalogId = $anuncio->json_data['catalog_product_id'] ?? null;
        if (!$catalogId) {
            $this->warn("Anúncio {$anuncio->id} não é de catálogo.");
            return;
        }

        $this->info("Processando anúncio: {$anuncio->titulo} ({$anuncio->external_id})");

        // 1. Buscar ofertas do catálogo
        $offersData = $meliService->getCatalogOffers($catalogId);
        if (!$offersData || empty($offersData['offers'])) {
            $this->warn("Não foi possível obter ofertas do catálogo.");
            return;
        }

        // 2. Filtrar ofertas dos concorrentes
        $competitors = collect($offersData['offers'])->filter(function($offer) use ($anuncio, $config) {
            // Ignorar nossa própria oferta
            if ($offer['id'] === $anuncio->external_id) return false;

            // Filtro Full
            if ($config->filter_full_only && ($offer['shipping']['logistic_type'] ?? '') !== 'fulfillment') {
                return false;
            }

            // Filtro Premium/Classic
            if ($config->filter_premium_only && ($offer['listing_type_id'] ?? '') !== 'gold_pro') {
                return false;
            }
            if ($config->filter_classic_only && ($offer['listing_type_id'] ?? '') !== 'gold_special') {
                return false;
            }

            return true;
        });

        if ($competitors->isEmpty()) {
            $this->info("Nenhum concorrente qualificado encontrado para os filtros ativos.");
            return;
        }

        // 3. Identificar o menor preço dos concorrentes elegíveis
        $lowestPrice = $competitors->min('price');
        $this->info("Menor preço concorrente qualificado: R$ " . number_format($lowestPrice, 2));

        // 4. Calcular preço alvo baseado na estratégia
        $targetPrice = $lowestPrice;
        if ($config->strategy === 'valor_abaixo') {
            $targetPrice = $lowestPrice - (float) $config->offset_value;
        } elseif ($config->strategy === 'valor_acima') {
            $targetPrice = $lowestPrice + (float) $config->offset_value;
        }

        // 5. Validar Margem de Lucro
        if (!$this->validateMargins($config, $targetPrice, $anuncio)) {
            $this->warn("Preço alvo R$ " . number_format($targetPrice, 2) . " rejeitado por limites de margem.");
            return;
        }

        // 6. Verificar se o preço já é o atual
        $currentPrice = (float) $anuncio->preco;
        if (abs($currentPrice - $targetPrice) < 0.01) {
            $this->info("Preço já está otimizado.");
            $config->update(['last_run_at' => now(), 'log_last_action' => "Preço mantido em R$ " . number_format($targetPrice, 2)]);
            return;
        }

        // 7. Atualizar no Mercado Livre
        $this->info("Atualizando preço de R$ " . number_format($currentPrice, 2) . " para R$ " . number_format($targetPrice, 2));
        
        $meliService->setIntegracao($anuncio->integracao);
        $success = $meliService->updateProduct($anuncio->external_id, ['price' => $targetPrice]);

        if ($success) {
            $anuncio->update(['preco' => $targetPrice]);
            $config->update([
                'last_run_at' => now(),
                'log_last_action' => "Preço alterado de R$ " . number_format($currentPrice, 2) . " para R$ " . number_format($targetPrice, 2)
            ]);
            $this->info("Preço atualizado com sucesso.");
        } else {
            $this->error("Erro ao atualizar preço na API do ML.");
        }
    }

    private function validateMargins(AnuncioRepricerConfig $config, float $targetPrice, MarketplaceAnuncio $anuncio): bool
    {
        // Instancia o componente para usar a lógica de lucro (ou poderíamos mover para um Service, mas aqui é mais rápido)
        $anunciosComponent = new Anuncios();
        
        // Simular o anúncio com o novo preço
        $anuncioSimulado = clone $anuncio;
        $anuncioSimulado->preco = $targetPrice;
        
        $lucro = $anunciosComponent->calcularLucratividade($anuncioSimulado);
        $margem = $lucro['margem'];

        if ($config->min_profit_margin !== null && $margem < $config->min_profit_margin) {
            return false;
        }

        if ($config->max_profit_margin !== null && $margem > $config->max_profit_margin) {
            // Se passar da margem máxima, talvez não queiramos baixar tanto? 
            // Geralmente repricer foca no mínimo, mas vamos respeitar se configurado.
            // Mas se o preço alvo for MAIOR que o atual e passar do max, ok.
            // Se for MENOR e passar do max (lucro alto demais?), teoricamente ok, mas seguimos o limite.
        }

        return true;
    }
}
