<?php

namespace App\Console\Commands;

use App\Services\FiscalService;
use Illuminate\Console\Command;

class AssociarNfeProdutos extends Command
{
    protected $signature = 'nfe:associar-produtos {--empresa= : ID da empresa para processar}';

    protected $description = 'Associa itens das NF-e com produtos do sistema via SKU ou GTIN';

    public function handle(): int
    {
        $empresaId = $this->option('empresa');

        if (! $empresaId) {
            $this->error('Informe o ID da empresa com --empresa=ID');

            return Command::FAILURE;
        }

        $this->info("Associando itens de NF-e com produtos para empresa {$empresaId}...");

        $fiscalService = new FiscalService;
        $resultado = $fiscalService->associarItensComProdutos((int) $empresaId);

        $this->info("Associados: {$resultado['associados']}");
        $this->info("Não encontrados: {$resultado['nao_encontrados']}");

        if (! empty($resultado['erros'])) {
            $this->warn('Erros:');
            foreach ($resultado['erros'] as $erro) {
                $this->warn("  - {$erro}");
            }
        }

        $this->info('Associação concluída!');

        return Command::SUCCESS;
    }
}
