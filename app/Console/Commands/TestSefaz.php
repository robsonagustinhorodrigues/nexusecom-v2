<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use App\Services\SefazEngine;

class TestSefaz extends Command
{
    protected $signature = 'test:sefaz {empresa_id}';
    protected $description = 'Busca 1 lote de NF-es no SEFAZ (max 50 docs). Repita a cada hora para avançar a fila.';

    public function handle(SefazEngine $sefaz)
    {
        $empresaId = $this->argument('empresa_id');
        $empresa = Empresa::find($empresaId);

        if (!$empresa) {
            $this->error("Empresa não encontrada.");
            return;
        }

        $currentNsu = $empresa->last_nsu ?? 0;
        $this->info("Empresa: {$empresa->nome}");
        $this->info("NSU atual: {$currentNsu}");

        try {
            $result = $sefaz->buscarPorNsu($empresa, $currentNsu);

            $this->info("✅ Lote processado: {$result['count']} documentos");
            $this->info("📍 NSU salvo: {$result['lastNsu']}");
            $this->info("📊 Limite global SEFAZ: {$result['maxNsu']}");

            if ($result['hasMore']) {
                $restantes = $result['maxNsu'] - $result['lastNsu'];
                $this->warn("⏳ Ainda há {$restantes} NSUs na fila. Aguarde 1 hora e rode novamente!");
            } else {
                $this->info("✅ Fila da SEFAZ esgotada!");
            }

        } catch (\Exception $e) {
            $this->error("Erro: " . $e->getMessage());
        }
    }
}
