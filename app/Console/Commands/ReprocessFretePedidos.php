<?php

namespace App\Console\Commands;

use App\Models\MarketplacePedido;
use App\Services\MeliIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReprocessFretePedidos extends Command
{
    protected $signature = 'pedidos:reprocess-frete {--empresa= : ID da empresa} {--limit=100 : Limite de pedidos}';
    protected $description = 'Reprocessa pedidos para buscar valor do frete na API do Mercado Livre';

    public function handle()
    {
        $empresaId = $this->option('empresa');
        $limit = (int) $this->option('limit');

        $query = MarketplacePedido::where('marketplace', 'mercadolivre')
            ->where('valor_frete', 0);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $total = $query->count();
        $this->info("Encontrados {$total} pedidos com frete zerado.");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $pedidos = $query->limit($limit)->get();
        $bar = $this->output->createProgressBar($pedidos->count());
        $bar->start();

        $atualizados = 0;
        $erros = 0;

        foreach ($pedidos as $pedido) {
            try {
                $meliService = new MeliIntegrationService($pedido->empresa_id);
                
                // Buscar detalhes do pedido na API do ML
                $orderData = $meliService->getOrderDetail($pedido->pedido_id);
                
                $frete = 0;
                
                // Tentar primeiro do order
                if (isset($orderData['shipping_cost']) && $orderData['shipping_cost'] > 0) {
                    $frete = (float) $orderData['shipping_cost'];
                }
                
                // Se não encontrou, buscar no shipment
                if ($frete == 0 && isset($orderData['shipping']['id'])) {
                    $shipment = $meliService->getShipment($orderData['shipping']['id']);
                    if (isset($shipment['shipping_option']['cost'])) {
                        $frete = (float) $shipment['shipping_option']['cost'];
                        // Se cost for 0 mas tem list_cost, usar o list_cost
                        if ($frete == 0 && isset($shipment['shipping_option']['list_cost'])) {
                            $frete = (float) $shipment['shipping_option']['list_cost'];
                        }
                    }
                }
                
                if ($frete > 0) {
                    
                    $pedido->update([
                        'valor_frete' => $frete,
                    ]);
                    
                    $atualizados++;
                    $this->line(" Pedido {$pedido->pedido_id}: Frete R$ " . number_format($frete, 2, ',', '.'));
                } else {
                    $this->line(" Pedido {$pedido->pedido_id}: Frete não encontrado na API");
                }

            } catch (\Exception $e) {
                $erros++;
                Log::error("Erro ao reprocessar frete do pedido {$pedido->pedido_id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Concluído! Atualizados: {$atualizados}, Erros: {$erros}");

        return self::SUCCESS;
    }
}
