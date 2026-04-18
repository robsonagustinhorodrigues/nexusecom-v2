<?php

namespace App\Console\Commands;

use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use App\Models\NfeItem;
use Illuminate\Console\Command;

class AssociateNfeProducts extends Command
{
    protected $signature = 'nfe:associate-products {--empresa= : ID da empresa} {--type=emitida : Tipo de NF (emitida|recebida)} {--limit=1000 : Limite de notas para processar} {--dry-run : Simular sem salvar}';

    protected $description = 'Associa produtos às notas fiscais por SKU/EAN';

    public function handle(): int
    {
        $empresaId = $this->option('empresa');
        $type = $this->option('type');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('MODO SIMULAÇÃO - Nenhum dado será alterado');
        }

        if ($type === 'emitida') {
            return $this->processEmitidas($empresaId, $dryRun, $limit);
        } else {
            return $this->processRecebidas($empresaId, $dryRun, $limit);
        }
    }

    private function processEmitidas($empresaId, bool $dryRun, int $limit): int
    {
        $query = NfeEmitida::whereHas('itens', function ($q) {
            $q->whereNull('product_id');
        });
        
        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $notas = $query->limit($limit)->get();
        $totalAssociated = 0;

        $bar = $this->output->createProgressBar($notas->count());
        $bar->start();

        foreach ($notas as $nota) {
            $grupoId = $nota->empresa?->grupo_id;
            if (!$grupoId) {
                $bar->advance();
                continue;
            }

            $itensPendentes = $nota->itens()->whereNull('product_id')->get();
            
            foreach ($itensPendentes as $item) {
                if (!$dryRun) {
                    if ($item->associateProduct($grupoId)) {
                        $totalAssociated++;
                    }
                } else {
                    // Dry run - just count
                    if (!empty($item->codigo_produto) || !empty($item->gtin)) {
                        $totalAssociated++;
                    }
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        
        $this->info("Processadas: {$notas->count()} notas");
        $this->info("Associados: {$totalAssociated} produtos");

        return Command::SUCCESS;
    }

    private function processRecebidas($empresaId, bool $dryRun, int $limit): int
    {
        $query = NfeRecebida::whereHas('itens', function ($q) {
            $q->whereNull('product_id');
        });
        
        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $notas = $query->limit($limit)->get();
        $totalAssociated = 0;

        $bar = $this->output->createProgressBar($notas->count());
        $bar->start();

        foreach ($notas as $nota) {
            $grupoId = $nota->empresa?->grupo_id;
            if (!$grupoId) {
                $bar->advance();
                continue;
            }

            $itensPendentes = $nota->itens()->whereNull('product_id')->get();
            
            foreach ($itensPendentes as $item) {
                if (!$dryRun) {
                    if ($item->associateProduct($grupoId)) {
                        $totalAssociated++;
                    }
                } else {
                    if (!empty($item->codigo_produto) || !empty($item->gtin)) {
                        $totalAssociated++;
                    }
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        
        $this->info("Processadas: {$notas->count()} notas");
        $this->info("Associados: {$totalAssociated} produtos");

        return Command::SUCCESS;
    }
}
