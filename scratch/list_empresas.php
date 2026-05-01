<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Empresa;

$empresas = Empresa::all(['id', 'nome', 'cnpj', 'last_nsu']);
foreach ($empresas as $empresa) {
    echo "ID: {$empresa->id} | Nome: {$empresa->nome} | CNPJ: {$empresa->cnpj} | Last NSU: {$empresa->last_nsu}\n";
}
