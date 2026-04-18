<?php

namespace App\Console\Commands;

use App\Models\MarketplacePedido;
use App\Models\Integracao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReprocessFretePedidos extends Command
{
    protected $signature = 'pedidos:reprocess-frete {--empresa= : ID da empresa} {--limit=100 : Limite de pedidos} {--force : Reprocessar todos, mesmo os que já têm frete}';
    protected $description = 'Reprocessa pedidos para buscar valor do frete real na API do Mercado Livre (endpoint /shipments/{id}/costs)';

    public function handle()
    {
        $empresaId = $this->option('empresa');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = MarketplacePedido::where('marketplace', 'mercadolivre');
        
        if (!$force) {
            $query->where('valor_frete', 0);
        }

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $total = $query->count();
        $this->info("Encontrados {$total} pedidos" . ($force ? ' (forçando reprocessamento)' : ' com frete zerado') . '.');

        if ($total === 0) {
            return self::SUCCESS;
        }

        $pedidos = $query->limit($limit)->get();
        $bar = $this->output->createProgressBar($pedidos->count());
        $bar->start();

        $atualizados = 0;
        $zerados = 0;
        $erros = 0;

        // Cache de tokens por empresa
        $tokens = [];

        foreach ($pedidos as $pedido) {
            try {
                // Obter token da empresa (com cache)
                if (!isset($tokens[$pedido->empresa_id])) {
                    $integ = Integracao::where('empresa_id', $pedido->empresa_id)
                        ->where('ativo', true)
                        ->first();
                    $tokens[$pedido->empresa_id] = $integ ? $integ->access_token : null;
                }
                $token = $tokens[$pedido->empresa_id];
                
                if (!$token) {
                    $erros++;
                    $bar->advance();
                    continue;
                }

                $jd = $pedido->json_data ?? [];
                $shipmentId = $jd['shipping']['id'] ?? null;

                if (!$shipmentId) {
                    $bar->advance();
                    continue;
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->get("https://api.mercadolibre.com/shipments/{$shipmentId}/costs");

                if (!$response->successful()) {
                    $erros++;
                    $bar->advance();
                    usleep(500000); 
                    continue;
                }

                $costsData = $response->json();
                $senderCost = 0;
                if (!empty($costsData['senders'])) {
                    foreach ($costsData['senders'] as $sender) {
                        $senderCost += floatval($sender['cost'] ?? 0);
                    }
                }

                $grossAmount = floatval($costsData['gross_amount'] ?? 0);

                if ($senderCost > 0 || $grossAmount > 0) {
                    $jd['shipping_costs_details'] = [
                        'sender_cost' => $senderCost,
                        'receiver_cost' => floatval($costsData['receiver'][0]['cost'] ?? 0),
                        'full_response' => $costsData
                    ];
                    
                    $pedido->valor_frete = $senderCost;
                    $pedido->json_data = $jd;
                    $pedido->save();

                    if ($senderCost > 0) {
                        $atualizados++;
                        $this->line(" Pedido {$pedido->pedido_id}: Frete Vendedor R$ " . number_format($senderCost, 2, ',', '.') . " (total R$ " . number_format($grossAmount, 2, ',', '.') . ")");
                    } else {
                        $zerados++;
                        if ($this->getOutput()->isVerbose()) {
                             $this->line(" Pedido {$pedido->pedido_id}: Frete R$ 0 (ML absorveu R$ " . number_format($grossAmount, 2, ',', '.') . ")");
                        }
                    }
                }

                usleep(300000); 

            } catch (\Exception $e) {
                $erros++;
                Log::error("Erro ao reprocessar frete do pedido {$pedido->pedido_id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Concluído! Atualizados: {$atualizados}, Zerados (ML absorveu): {$zerados}, Erros: {$erros}");

        return self::SUCCESS;
    }
}
