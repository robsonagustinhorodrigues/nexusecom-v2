<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\Tarefa;
use App\Services\FiscalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportarNfeXmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $empresaId;
    public $xmlPath;
    public $tarefaId;

    public function __construct($empresaId, $xmlPath, $tarefaId)
    {
        $this->empresaId = $empresaId;
        $this->xmlPath = $xmlPath;
        $this->tarefaId = $tarefaId;
    }

    public function handle()
    {
        $tarefa = Tarefa::find($this->tarefaId);
        
        try {
            $xmlContent = Storage::disk('local')->get($this->xmlPath);
            
            $fiscalService = new FiscalService;
            $fiscalService->importXml($xmlContent, $this->empresaId);

            if ($tarefa) {
                $tarefa->update(['status' => 'concluido', 'processado' => 1]);
            }
        } catch (\Exception $e) {
            Log::error("Erro no ImportarNfeXmlJob para empresa {$this->empresaId}: " . $e->getMessage());
            if ($tarefa) {
                $tarefa->update(['status' => 'erro', 'mensagem' => 'Erro: ' . $e->getMessage()]);
            }
        } finally {
            // Limpa arquivo temporário
            Storage::disk('local')->delete($this->xmlPath);
        }
    }
}
