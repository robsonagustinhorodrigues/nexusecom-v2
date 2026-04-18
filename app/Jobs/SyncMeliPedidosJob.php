<?php

namespace App\Jobs;

use App\Models\Tarefa;
use App\Services\MeliIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMeliPedidosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $empresaId,
        public int $userId,
        public int $tarefaId,
        public int $limit = 50,
        public int $maxPages = 20,
        public int $maxOrders = 1000
    ) {}

    public function handle(MeliIntegrationService $service): void
    {
        $service = new MeliIntegrationService($this->empresaId);

        $tarefa = Tarefa::find($this->tarefaId);

        if (! $service->isConnected()) {
            Log::warning("SyncMeliPedidosJob: Meli não conectado para empresa {$this->empresaId}");
            if ($tarefa) {
                $tarefa->update([
                    'status' => 'falhou',
                    'mensagem' => 'Meli não conectado',
                    'finished_at' => now(),
                ]);
            }

            return;
        }

        if ($tarefa) {
            $tarefa->update([
                'status' => 'processando',
                'mensagem' => 'Iniciando sincronização...',
            ]);
        }

        Log::info("Iniciando SyncMeliPedidosJob para empresa {$this->empresaId} - Tarefa #{$this->tarefaId}");

        $imported = 0;
        $errors = 0;
        $page = 0;
        $totalOrders = 0;

        while ($page < $this->maxPages && $imported < $this->maxOrders) {
            $offset = $page * $this->limit;

            $result = $service->getOrders([
                'limit' => $this->limit,
                'offset' => $offset,
                'sort' => 'date_desc',
            ]);

            if (isset($result['error'])) {
                Log::error("SyncMeliPedidosJob: Erro na página {$page}: {$result['error']}");
                if ($tarefa) {
                    $tarefa->update([
                        'status' => $errors > 0 ? 'concluido_com_erros' : 'concluido',
                        'mensagem' => $result['error'],
                        'finished_at' => now(),
                    ]);
                }
                break;
            }

            $orders = $result['results'] ?? [];
            $totalOrders += count($orders);

            if (empty($orders)) {
                Log::info("SyncMeliPedidosJob: Sem mais pedidos na página {$page}");
                break;
            }

            foreach ($orders as $order) {
                try {
                    $service->importOrder($order);
                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("SyncMeliPedidosJob: Erro ao importar pedido {$order['id']}: ".$e->getMessage());
                }
            }

            if ($tarefa) {
                $tarefa->update([
                    'processado' => $totalOrders,
                    'sucesso' => $imported - $errors,
                    'falha' => $errors,
                    'mensagem' => "Processando página {$page}...",
                ]);
            }

            if (count($orders) < $this->limit) {
                break;
            }

            $page++;
            usleep(100000);
        }

        $status = $errors > 0 ? 'concluido_com_erros' : 'concluido';
        $mensagem = "Importados: {$imported}, Erros: {$errors}";

        if ($tarefa) {
            $tarefa->update([
                'processado' => $totalOrders,
                'total' => max($totalOrders, $tarefa->total),
                'sucesso' => $imported - $errors,
                'falha' => $errors,
                'status' => $status,
                'mensagem' => $mensagem,
                'finished_at' => now(),
            ]);
        }

        Log::info("SyncMeliPedidosJob concluído para empresa {$this->empresaId}. Importados: {$imported}, Erros: {$errors}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SyncMeliPedidosJob falhou para empresa {$this->empresaId}: ".$exception->getMessage());

        $tarefa = Tarefa::find($this->tarefaId);
        if ($tarefa) {
            $tarefa->update([
                'status' => 'falhou',
                'mensagem' => 'Erro: '.$exception->getMessage(),
                'finished_at' => now(),
            ]);
        }
    }
}
