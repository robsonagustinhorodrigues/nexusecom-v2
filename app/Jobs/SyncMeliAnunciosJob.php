<?php

namespace App\Jobs;

use App\Models\Integracao;
use App\Services\MeliService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMeliAnunciosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Integracao $integracao)
    {}

    public function handle(MeliService $meliService): void
    {
        try {
            $count = $meliService->syncAnuncios($this->integracao);
            Log::info("Sync ML concluído para integração {$this->integracao->id}. Itens: {$count}");
        } catch (\Exception $e) {
            Log::error("Erro no Job SyncMeliAnuncios: " . $e->getMessage());
        }
    }
}
