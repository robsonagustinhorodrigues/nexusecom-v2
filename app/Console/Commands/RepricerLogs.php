<?php

namespace App\Console\Commands;

use App\Models\RepricerLog;
use Illuminate\Console\Command;

class RepricerLogs extends Command
{
    protected $signature = 'repricer:logs {--anuncio_id= : Filtrar por ID do anúncio} {--empresa_id= : Filtrar por ID da empresa} {--limit=20 : Número de registros} {--status= : Filtrar por status (success, skipped, error)}';

    protected $description = 'Mostra os logs de execução do Repricer';

    public function handle()
    {
        $query = RepricerLog::with(['anuncio', 'empresa']);

        if ($anuncioId = $this->option('anuncio_id')) {
            $query->where('marketplace_anuncio_id', $anuncioId);
        }

        if ($empresaId = $this->option('empresa_id')) {
            $query->where('empresa_id', $empresaId);
        }

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $logs = $query->orderBy('created_at', 'desc')->limit($this->option('limit'))->get();

        if ($logs->isEmpty()) {
            $this->info("Nenhum log encontrado.");
            return;
        }

        $this->info("📊 Logs do Repricer - Ultimos {$logs->count()} registros\n");
        $this->line(str_repeat('=', 120));

        foreach ($logs as $log) {
            $statusEmoji = match($log->status) {
                'success' => '✅',
                'skipped' => '⏭️',
                'error' => '❌',
                default => '❓'
            };

            $this->line("\n{$statusEmoji} [" . $log->created_at->format('d/m/Y H:i:s') . "]");
            $this->line("   Anúncio: " . ($log->anuncio?->titulo ?? 'N/A') . " (ID: {$log->marketplace_anuncio_id})");
            $this->line("   Estratégia: {$log->strategy}");
            
            if ($log->preco_anterior && $log->preco_novo) {
                $this->line("   Preço: R$ " . number_format($log->preco_anterior, 2, ',', '.') . " → R$ " . number_format($log->preco_novo, 2, ',', '.'));
            }
            
            if ($log->menor_concorrente) {
                $this->line("   Menor Concorrente: R$ " . number_format($log->menor_concorrente, 2, ',', '.'));
            }
            
            if ($log->margem_lucro) {
                $this->line("   Margem de Lucro: " . number_format($log->margem_lucro, 2, ',', '.') . "%");
            }

            $this->line("   Status: {$log->status} - {$log->mensagem}");
        }

        $this->line("\n" . str_repeat('=', 120));
    }
}
