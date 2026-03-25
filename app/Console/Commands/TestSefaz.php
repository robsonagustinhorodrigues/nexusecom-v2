<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use App\Services\SefazEngine;

class TestSefaz extends Command
{
    protected $signature = 'test:sefaz {empresa_id}';
    protected $description = 'Test Sefaz Import';

    public function handle(SefazEngine $sefaz)
    {
        $empresaId = $this->argument('empresa_id');
        $empresa = Empresa::find($empresaId);

        if (!$empresa) {
            $this->error("Empresa não encontrada.");
            return;
        }

        $this->info("Iniciando busca para: {$empresa->nome}");

        try {
            $result = $sefaz->buscarNovasNotas($empresa);
            $this->info("Resultado:");
            print_r($result);
        } catch (\Exception $e) {
            $this->error("Erro: " . $e->getMessage());
        }
    }
}
