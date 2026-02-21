<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReprocessarNfs extends Command
{
    protected $signature = 'reprocessar:nfs {empresaId? : ID da empresa (opcional)} {--chunk=500} {--offset=0 : Offset inicial (pular N registros)} {--limit=0 : Limite de registros (0 = todos)}';

    protected $description = 'Reprocessa todas as NF-es para separar entrada de saída corretamente';

    public function handle()
    {
        $empresaId = $this->argument('empresaId');
        $chunkSize = (int) $this->option('chunk');
        
        $empresas = $empresaId 
            ? [Empresa::find($empresaId)] 
            : Empresa::all();
        
        if (!is_iterable($empresas) || (is_array($empresas) && empty($empresas))) {
            $this->error('Nenhuma empresa encontrada ou erro ao buscar empresas.');
            return 1;
        }

        $totalMovidas = 0;
        
        foreach ($empresas as $empresa) {
            if (!$empresa) continue;
            
            $movidas = $this->processarEmpresa($empresa, $chunkSize);
            $totalMovidas += $movidas;
        }

        $this->info("Total: {$totalMovidas} notas movidas para emitidas!");
        return 0;
    }

    private function processarEmpresa(Empresa $empresa, int $chunkSize): int
    {
        $this->info("Processando empresa {$empresa->id} ({$empresa->nome_fantasia})...");
        
        $cnpjEmpresa = preg_replace('/[^0-9]/', '', $empresa->cnpj ?? '');
        $cnpjEmpresa = ltrim($cnpjEmpresa, '0');
        
        if (empty($cnpjEmpresa)) {
            $this->warn("  Empresa sem CNPJ, pulando...");
            return 0;
        }

        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');

        $query = NfeRecebida::where('empresa_id', $empresa->id)
            ->whereNotNull('chave')
            ->orderBy('id');

        if ($offset > 0) {
            $query->skip($offset);
        }
        if ($limit > 0) {
            $query->take($limit);
        }

        $totalRegistros = (clone $query)->count();
        $this->info("  Total de registros a processar: {$totalRegistros}");

        $movidas = 0;
        $processados = 0;
        $erros = 0;
        $batch = [];
        
        $query->chunk($chunkSize, function ($nfeRecebidas) use ($empresa, $cnpjEmpresa, &$movidas, &$processados, &$erros, &$batch, $totalRegistros) {
            foreach ($nfeRecebidas as $nfe) {
                $chave = $nfe->chave;
                if (empty($chave)) continue;
                
                $cnpjEmitente = substr(preg_replace('/[^0-9]/', '', $chave), 6, 14);
                $cnpjEmitente = ltrim($cnpjEmitente, '0');
                
                if (!empty($cnpjEmitente) && $cnpjEmitente === $cnpjEmpresa) {
                    $existe = NfeEmitida::where('chave', $chave)
                        ->where('empresa_id', $empresa->id)
                        ->exists();
                    
                    if (!$existe) {
                        $batch[] = [
                            'empresa_id' => $empresa->id,
                            'chave' => $nfe->chave,
                            'numero' => $nfe->numero,
                            'serie' => $nfe->serie ?? $this->extrairSerieDaChave($nfe->chave),
                            'cliente_nome' => 'Cliente',
                            'cliente_cnpj' => '',
                            'valor_total' => $nfe->valor_total,
                            'data_emissao' => $nfe->data_emissao,
                            'status' => 'autorizada',
                            'xml_path' => $nfe->xml_path,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $movidas++;
                    }
                }
                $processados++;
            }

            // Batch insert (groups of up to 500)
            if (!empty($batch)) {
                foreach (array_chunk($batch, 500) as $batchChunk) {
                    try {
                        DB::table('nfe_emitidas')->insert($batchChunk);
                    } catch (\Exception $e) {
                        // Fallback: insert one by one to skip only the problematic ones
                        foreach ($batchChunk as $row) {
                            try {
                                DB::table('nfe_emitidas')->insert($row);
                            } catch (\Exception $e2) {
                                $this->warn("    ⚠ Erro ao inserir chave {$row['chave']}: " . $e2->getMessage());
                                $erros++;
                            }
                        }
                    }
                }
                $batch = [];
            }

            $this->info("  Progresso: {$processados}/{$totalRegistros} processados, {$movidas} movidas, {$erros} erros");

            gc_collect_cycles();
        });

        // Insert any remaining batch
        if (!empty($batch)) {
            foreach (array_chunk($batch, 500) as $batchChunk) {
                try {
                    DB::table('nfe_emitidas')->insert($batchChunk);
                } catch (\Exception $e) {
                    foreach ($batchChunk as $row) {
                        try {
                            DB::table('nfe_emitidas')->insert($row);
                        } catch (\Exception $e2) {
                            $this->warn("    ⚠ Erro ao inserir chave {$row['chave']}: " . $e2->getMessage());
                            $erros++;
                        }
                    }
                }
            }
        }
        
        $this->info("  {$movidas} notas movidas para emitidas");
        return $movidas;
    }

    /**
     * Extrai a série da chave NFe (posição 22-25, 3 dígitos)
     */
    private function extrairSerieDaChave(string $chave): int
    {
        $chaveNumerica = preg_replace('/[^0-9]/', '', $chave);
        if (strlen($chaveNumerica) >= 25) {
            $serie = substr($chaveNumerica, 22, 3);
            return (int) ltrim($serie, '0') ?: 1;
        }
        return 1;
    }
}