<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;

try {
    $files = Storage::disk('google')->files('/');
    echo "Arquivos encontrados no Google Drive:\n";
    print_r($files);
} catch (\Exception $e) {
    echo "Erro ao conectar no Google Drive: " . $e->getMessage();
}
