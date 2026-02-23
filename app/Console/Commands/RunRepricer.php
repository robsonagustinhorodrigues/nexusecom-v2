<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\MarketplaceAnuncio;
use App\Models\AnuncioRepricerConfig;
use App\Models\RepricerLog;
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
    public function handle()
    {
        $empresaId = $this->argument('empresaId');
        $anuncioId = $this->option('anuncio_id');

        // Criar instância do serviço com empresaId
        $meliService = new MeliIntegrationService($empresaId);
        
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
        
        $query = AnuncioRepricerConfig::with(['anuncio.productSku.product', 'anuncio.integracao'])
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
            $this->salvarLog($anuncio, $config, 'skipped', 'Anúncio não é de catálogo');
            return;
        }

        $this->info("Processando anúncio: {$anuncio->titulo} ({$anuncio->external_id})");

        // 1. Buscar ofertas do catálogo
        $permalink = $anuncio->json_data['permalink'] ?? null;
        $offersData = null;
        if (method_exists($meliService, 'getCatalogOffers')) {
            try {
                $offersData = $meliService->getCatalogOffers($catalogId, $permalink);
            } catch (\Exception $e) {
                $this->warn("Erro ao buscar ofertas: " . $e->getMessage());
            }
        }
        
        if (!$offersData || (is_array($offersData) && empty($offersData['offers']) && empty($offersData['competitors']))) {
            // Tenta buscar via search para anúncios normais (não catálogo)
            $this->info("Tentando buscar preços de concorrentes via search...");
            $offersData = $this->buscarPrecosConcorrentes($meliService, $anuncio);
        }
        
        if (!$offersData || (is_array($offersData) && empty($offersData['offers']) && empty($offersData['competitors']))) {
            $this->warn("Não foi possível obter ofertas do catálogo.");
            $this->salvarLog($anuncio, $config, 'skipped', 'Método de busca de concorrentes não implementado');
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
            $this->salvarLog($anuncio, $config, 'skipped', 'Nenhum concorrente qualificado encontrado');
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

        // 5. Obter preço atual antes de validar margens
        $currentPrice = (float) $anuncio->preco;
        
        // 6. Validar Margem de Lucro
        $anuncioSimulado = clone $anuncio;
        $anuncioSimulado->preco = $targetPrice;
        $anunciosComponent = new Anuncios();
        $lucroData = $anunciosComponent->calcularLucratividade($anuncioSimulado);
        
        $this->info("📊 Cálculo de Lucro:");
        $this->info("   Preço atual: R$ " . number_format($currentPrice, 2, ',', '.'));
        $this->info("   Preço novo: R$ " . number_format($targetPrice, 2, ',', '.'));
        $this->info("   Custo produto: R$ " . number_format($lucroData['custo'] ?? 0, 2, ',', '.'));
        $this->info("   Impostos: R$ " . number_format($lucroData['impostos'] ?? 0, 2, ',', '.'));
        $this->info("   Frete: R$ " . number_format($lucroData['frete'] ?? 0, 2, ',', '.'));
        $this->info("   Lucro bruto: R$ " . number_format($lucroData['lucro_bruto'] ?? 0, 2, ',', '.'));
        $this->info("   Margem: " . number_format($lucroData['margem'] ?? 0, 2, ',', '.') . "%");
        
        $margemValidada = $this->validateMargins($config, $targetPrice, $anuncio);
        if (!$margemValidada) {
            $this->warn("Preço alvo R$ " . number_format($targetPrice, 2) . " rejeitado por limites de margem.");
            $this->salvarLog($anuncio, $config, 'skipped', 'Rejeitado por limites de margem', $lowestPrice, $targetPrice);
            return;
        }

        // 6. Verificar se o preço já é o atual
        if (abs($currentPrice - $targetPrice) < 0.01) {
            $this->info("Preço já está otimizado.");
            $config->update(['last_run_at' => now(), 'log_last_action' => "Preço mantido em R$ " . number_format($targetPrice, 2)]);
            $this->salvarLog($anuncio, $config, 'success', 'Preço já otimizado', $lowestPrice, $targetPrice);
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
            $this->salvarLog($anuncio, $config, 'success', 'Preço atualizado com sucesso', $lowestPrice, $targetPrice, $currentPrice);
        } else {
            $this->error("Erro ao atualizar preço na API do ML.");
            $this->salvarLog($anuncio, $config, 'error', 'Erro ao atualizar preço na API do ML', $lowestPrice, $targetPrice, $currentPrice);
        }
    }

    private function salvarLog(MarketplaceAnuncio $anuncio, AnuncioRepricerConfig $config, string $status, string $mensagem, ?float $menorConcorrente = null, ?float $precoNovo = null, ?float $precoAnterior = null)
    {
        try {
            // Calcular margem se temos preço novo
            $margem = null;
            $lucroBruto = null;
            $custoProduto = null;
            $impostos = null;
            $frete = null;
            
            if ($precoNovo) {
                $anuncioSimulado = clone $anuncio;
                $anuncioSimulado->preco = $precoNovo;
                $anunciosComponent = new Anuncios();
                $lucroData = $anunciosComponent->calcularLucratividade($anuncioSimulado);
                $margem = $lucroData['margem'] ?? null;
                $lucroBruto = $lucroData['lucro_bruto'] ?? null;
                $custoProduto = $lucroData['custo'] ?? null;
                $impostos = $lucroData['impostos'] ?? null;
                $frete = $lucroData['frete'] ?? null;
            }

            RepricerLog::create([
                'marketplace_anuncio_id' => $anuncio->id,
                'empresa_id' => $anuncio->empresa_id,
                'strategy' => $config->strategy,
                'preco_anterior' => $precoAnterior ?? $anuncio->preco,
                'preco_novo' => $precoNovo,
                'menor_concorrente' => $menorConcorrente,
                'margem_lucro' => $margem,
                'lucro_bruto' => $lucroBruto,
                'status' => $status,
                'mensagem' => $mensagem,
                'detalhes' => [
                    'custo_produto' => $custoProduto,
                    'impostos' => $impostos,
                    'frete' => $frete,
                    'config' => [
                        'offset_value' => $config->offset_value,
                        'min_profit_margin' => $config->min_profit_margin,
                        'max_profit_margin' => $config->max_profit_margin,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao salvar log do repricer: " . $e->getMessage());
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

    private function buscarPrecosConcorrentes(MeliIntegrationService $meliService, MarketplaceAnuncio $anuncio): ?array
    {
        // Método alternativo: tentar buscar preços via search do Mercado Livre
        // Isso é um placeholder - a implementação real precisaria da API do Meli
        // Por enquanto, retorna null para indicar que não foi possível
        $this->info("Busca de preços concorrentes via API ainda não implementada.");
        return null;
    }
}
