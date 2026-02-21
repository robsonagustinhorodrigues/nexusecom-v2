<?php

namespace App\Jobs;

use App\Models\Tarefa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportNfeMagaluJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $empresaId,
        public int $userId,
        public int $tarefaId,
        public string $dataDe,
        public string $dataAte
    ) {}

    public function handle(): void
    {
        $tarefa = Tarefa::find($this->tarefaId);

        $integracao = \App\Models\Integracao::where('empresa_id', $this->empresaId)
            ->where('marketplace', 'magalu')
            ->where('ativo', true)
            ->first();

        if (! $integracao) {
            if ($tarefa) {
                $tarefa->update([
                    'status' => 'falhou',
                    'mensagem' => 'Magalu não conectado',
                    'finished_at' => now(),
                ]);
            }

            return;
        }

        if ($tarefa) {
            $tarefa->update([
                'status' => 'processando',
                'mensagem' => 'Iniciando importação de NFes da Magalu...',
            ]);
        }

        Log::info("Iniciando ImportNfeMagaluJob para empresa {$this->empresaId}, período: {$this->dataDe} a {$this->dataAte}");

        $imported = 0;
        $errors = 0;

        if ($tarefa) {
            $tarefa->update([
                'processado' => 1,
                'sucesso' => 0,
                'falha' => 0,
                'mensagem' => 'Importação concluída (Magalu API não implementada)',
                'status' => 'concluido',
                'finished_at' => now(),
            ]);
        }

        Log::info('ImportNfeMagaluJob concluído. Funcionalidade em desenvolvimento.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ImportNfeMagaluJob falhou para empresa {$this->empresaId}: ".$exception->getMessage());

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
