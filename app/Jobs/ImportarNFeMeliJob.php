<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\Tarefa;
use App\Services\Meli\MeliNFeImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportarNFeMeliJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $empresa;
    public $dataInicio;
    public $dataFim;
    public $tarefaId;

    /**
     * Create a new job instance.
     */
    public function __construct(Empresa $empresa, string $dataInicio, string $dataFim, int $tarefaId = null)
    {
        $this->empresa = $empresa;
        $this->dataInicio = $dataInicio;
        $this->dataFim = $dataFim;
        $this->tarefaId = $tarefaId;
        $this->onQueue('importacao_nfe');
    }

    /**
     * Execute the job.
     */
    public function handle(MeliNFeImportService $meliService)
    {
        $tarefa = null;
        
        if ($this->tarefaId) {
            $tarefa = Tarefa::find($this->tarefaId);
        }

        try {
            Log::info("Iniciando importação de NF-es do Mercado Livre para empresa: {$this->empresa->nome}");
            
            if ($tarefa) {
                $tarefa->update(['status' => 'processando', 'processado' => 1]);
            }

            $result = $meliService->execute($this->empresa, $this->dataInicio, $this->dataFim);

            if ($tarefa) {
                if (!empty($result['errors'])) {
                    $tarefa->update([
                        'status' => 'erro',
                        'processado' => 1,
                        'mensagem' => implode(', ', $result['errors'])
                    ]);
                } else {
                    $tarefa->update([
                        'status' => 'concluido',
                        'processado' => 1,
                        'resultado' => json_encode($result)
                    ]);
                }
            }

            Log::info("Importação de NF-es do Mercado Livre concluída. Empresa: {$this->empresa->nome}");

        } catch (\Exception $e) {
            Log::error("Job ImportarNFeMeliJob Falhou: " . $e->getMessage());
            
            if ($tarefa) {
                $tarefa->update([
                    'status' => 'erro',
                    'processado' => 0,
                    'mensagem' => $e->getMessage()
                ]);
            }
        }
    }
}
