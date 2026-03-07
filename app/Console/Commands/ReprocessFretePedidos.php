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

                // Extrair shipping_id do json_data
                $jd = $pedido->json_data ?? [];
                $shipmentId = $jd['shipping']['id'] ?? null;

                if (!$shipmentId) {
                    $bar->advance();
                    continue;
                }

                // Chamar o endpoint /shipments/{id}/costs que retorna o custo real do vendedor
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->get("https://api.mercadolibre.com/shipments/{$shipmentId}/costs");

                if (!$response->successful()) {
                    $erros++;
                    $this->line(" Pedido {$pedido->pedido_id}: Erro HTTP " . $response->status());
                    $bar->advance();
                    // Rate limit - esperar um pouco
                    usleep(500000); // 500ms
                    continue;
                }

                $costsData = $response->json();
                
                // O custo real do vendedor está em senders[0].cost
                $senderCost = 0;
                if (!empty($costsData['senders'])) {
                    foreach ($costsData['senders'] as $sender) {
                        $senderCost += floatval($sender['cost'] ?? 0);
                    }
                }

                // gross_amount é o custo total do frete (independente de quem paga)
                $grossAmount = floatval($costsData['gross_amount'] ?? 0);

                if ($senderCost > 0) {
                    $pedido->update([
                        'valor_frete' => $senderCost,
                    ]);
                    $atualizados++;
                    $this->line(" Pedido {$pedido->pedido_id}: Frete Vendedor R$ " . number_format($senderCost, 2, ',', '.') . " (total R$ " . number_format($grossAmount, 2, ',', '.') . ")");
                } else {
                    $zerados++;
                    if ($this->getOutput()->isVerbose()) {
                        $this->line(" Pedido {$pedido->pedido_id}: Frete R$ 0 (ML absorveu R$ " . number_format($grossAmount, 2, ',', '.') . ")");
                    }
                }

                // Rate limit - esperar entre requests
                usleep(300000); // 300ms

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
