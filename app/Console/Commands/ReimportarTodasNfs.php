<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReimportarTodasNfs extends Command
{
    protected $signature = 'importar:nfs-all {--chunk=100 : Quantos XMLs por vez}';
    protected $description = 'Re-importa todas as NF-es das pastas para o banco de dados';

    public function handle()
    {
        $chunkSize = (int) $this->option('chunk');
        
        // Mapeamento CNPJ -> Empresa ID
        $cnpjToEmpresaId = [
            '57297069000146' => 4,  // MaxLider
            '61778473000109' => 5,  // LideraMais
            '47650333000120' => 6,  // LideraMix
        ];
        
        $basePath = '/home/robson/Downloads/nfes/nfes';
        
        $totalGeral = 0;
        
        foreach ($cnpjToEmpresaId as $cnpj => $empresaId) {
            $path = "{$basePath}/{$cnpj}";
            
            if (!is_dir($path)) {
                $this->warn("Pasta não encontrada: {$path}");
                continue;
            }
            
            // Busca todos os XMLs recursivamente
            $xmlFiles = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'xml') {
                    $xmlFiles[] = $file->getPathname();
                }
            }
            
            $total = count($xmlFiles);
            
            if ($total === 0) {
                $this->warn("Nenhum XML encontrado em {$path}");
                continue;
            }
            
            $this->info("========================================");
            $this->info("Empresa ID {$empresaId} (CNPJ: {$cnpj})");
            $this->info("Encontrados {$total} arquivos XML");
            $this->info("Processando em chunks de {$chunkSize}...");
            
            $importados = 0;
            $erros = 0;
            $chunk = [];
            
            foreach ($xmlFiles as $index => $xmlPath) {
                $chunk[] = $xmlPath;
                
                if (count($chunk) >= $chunkSize || $index === $total - 1) {
                    // Processa o chunk
                    foreach ($chunk as $xmlFile) {
                        try {
                            $content = file_get_contents($xmlFile);
                            if ($content) {
                                $nomeArquivo = basename($xmlFile);
                                $fiscalService = new \App\Services\FiscalService();
                                $result = $fiscalService->importXml($content, $empresaId, $nomeArquivo);
                                if ($result) {
                                    $importados++;
                                }
                            }
                        } catch (\Exception $e) {
                            $erros++;
                        }
                    }
                    
                    $progress = round(($index + 1) / $total * 100);
                    $this->info("Progresso: {$progress}% ({$index}/{$total}) - Importados: {$importados}, Erros: {$erros}");
                    
                    $chunk = [];
                }
            }
            
            $this->info("Concluído! Importados: {$importados}, Erros: {$erros}");
            $totalGeral += $total;
        }
        
        $this->info("========================================");
        $this->info("Importação total concluída!");
        $this->info("Total de arquivos processados: {$totalGeral}");
        
        return 0;
    }
}
