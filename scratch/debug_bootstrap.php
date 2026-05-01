<?php
echo "Iniciando autoload...\n";
require __DIR__ . '/../vendor/autoload.php';
echo "Iniciando bootstrap app...\n";
$app = require_once __DIR__ . '/../bootstrap/app.php';
echo "Iniciando kernel bootstrap...\n";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "Bootstrap concluído!\n";

use App\Models\Empresa;
echo "Buscando empresas...\n";
$empresas = Empresa::all();
echo "Empresas encontradas: " . $empresas->count() . "\n";
foreach ($empresas as $e) {
    echo "ID: {$e->id} | Nome: {$e->nome}\n";
}
