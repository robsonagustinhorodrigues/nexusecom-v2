<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Jobs\SyncFileToDriveJob;
use Illuminate\Support\Facades\Storage;

// 1. Criar um arquivo de teste local
$path = 'test_upload.txt';
Storage::disk('local')->put($path, 'Teste de sincronização NexusEcom ' . date('Y-m-d H:i:s'));

echo "Arquivo criado localmente em: " . storage_path('app/' . $path) . "\n";

// 2. Disparar o Job de sincronização
SyncFileToDriveJob::dispatch($path);

echo "Job de sincronização disparado. Verifique seu worker ou logs em storage/logs/laravel.log\n";
