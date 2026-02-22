<?php

namespace App\Jobs;

use App\Models\Empresa;
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
    public $xmlContent;

    /**
     * Create a new job instance.
     */
    public function __construct(Empresa $empresa, string $xmlContent)
    {
        $this->empresa = $empresa;
        $this->xmlContent = $xmlContent;
        $this->onQueue('importacao_nfe');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Processa o XML usando o FiscalService existente
            $fiscalService = new \App\Services\FiscalService($this->empresa);
            $response = $fiscalService->processXmlContent($this->empresa, $this->xmlContent);

            if (!empty($response['errors'])) {
                Log::warning("Erro ao processar XML Meli via Job: " . json_encode($response['errors']));
            }

            Log::info("NF-e Meli importada com sucesso para empresa: " . $this->empresa->nome);

        } catch (\Exception $e) {
            Log::error("Job ImportarNFeMeliJob Falhou: " . $e->getMessage());
        }
    }
}
