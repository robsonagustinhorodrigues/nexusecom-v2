<?php

namespace App\Console\Commands;

use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use App\Services\FiscalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReprocessarNfeItens extends Command
{
    protected $signature = 'nfe:reprocessar-itens {--empresa= : ID da empresa para processar} {--tipo= : Tipo de NF (emitida|recebida|ambas)} {--associar : Associar itens com produtos automaticamente}';

    protected $description = 'Reprocessa os itens das NF-e existentes no sistema';

    public function handle(): int
    {
        $empresaId = $this->option('empresa');
        $tipo = $this->option('tipo') ?? 'ambas';

        $fiscalService = new FiscalService;

        $this->info('Iniciando reprocessamento de itens de NF-e...');

        if ($tipo === 'emitida' || $tipo === 'ambas') {
            $query = NfeEmitida::query();
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            $total = $query->count();
            $this->info("Processando {$total} NF-e Emitidas...");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $query->chunk(100, function ($notas) use ($fiscalService, $bar) {
                foreach ($notas as $nota) {
                    if ($nota->xml_path && Storage::exists($nota->xml_path)) {
                        $xmlContent = Storage::get($nota->xml_path);

                        $itensExistentes = $nota->itens()->count();
                        if ($itensExistentes > 0) {
                            $nota->itens()->delete();
                        }

                        $fiscalService->processarItensNfe($xmlContent, $nota->id, null);
                    }
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
            $this->info('NF-e Emitidas processadas.');
        }

        if ($tipo === 'recebida' || $tipo === 'ambas') {
            $query = NfeRecebida::query();
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            $total = $query->count();
            $this->info("Processando {$total} NF-e Recebidas...");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $query->chunk(100, function ($notas) use ($fiscalService, $bar) {
                foreach ($notas as $nota) {
                    if ($nota->xml_path && Storage::exists($nota->xml_path)) {
                        $xmlContent = Storage::get($nota->xml_path);

                        $itensExistentes = $nota->itens()->count();
                        if ($itensExistentes > 0) {
                            $nota->itens()->delete();
                        }

                        $fiscalService->processarItensNfe($xmlContent, null, $nota->id);
                    }
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
            $this->info('NF-e Recebidas processadas.');
        }

        if ($this->option('associar') && $empresaId) {
            $this->info('Associando itens com produtos...');
            $fiscalService = new FiscalService;
            $resultado = $fiscalService->associarItensComProdutos((int) $empresaId);
            $this->info("Associados: {$resultado['associados']}, Não encontrados: {$resultado['nao_encontrados']}");
        }

        $this->info('Reprocessamento concluído!');

        return Command::SUCCESS;
    }
}
