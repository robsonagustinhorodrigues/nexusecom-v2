<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SyncFileToDriveJob implements ShouldQueue
{
    use Queueable;

    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function handle(): void
    {
        try {
            if (Storage::disk('local')->exists($this->path)) {
                $content = Storage::disk('local')->get($this->path);
                Storage::disk('google')->put($this->path, $content);
                Log::info("Arquivo sincronizado com sucesso no Google Drive: {$this->path}");
            }
        } catch (\Exception $e) {
            Log::error("Erro ao sincronizar arquivo com Google Drive: {$this->path}. Erro: " . $e->getMessage());
        }
    }
}
