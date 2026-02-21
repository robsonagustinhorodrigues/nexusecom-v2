<?php

namespace App\Console\Commands;

use App\Services\FiscalService;
use Illuminate\Console\Command;

class ImportarNfsXml extends Command
{
    protected $signature = 'importar:nfs {empresaId : ID da empresa} {path : Caminho para pasta com XMLs} {--chunk=50 : Quantos XMLs por vez}';

    protected $description = 'Importa NF-es de uma pasta de XMLs';

    public function handle()
    {
        $empresaId = $this->argument('empresaId');
        $path = $this->argument('path');
        $chunkSize = (int) $this->option('chunk');

        if (! is_dir($path)) {
            $this->error("Diretório não encontrado: {$path}");

            return 1;
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
            $this->warn("Nenhum arquivo XML encontrado em {$path}");

            return 0;
        }

        $this->info("Encontrados {$total} arquivos XML");
        $this->info("Processando em chunks de {$chunkSize}...");

        $fiscalService = new FiscalService;
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
                            $result = $fiscalService->importXml($content, $empresaId, $nomeArquivo);
                            if ($result) {
                                $importados++;
                                // Remove o XML após importação bem-sucedida
                                if (unlink($xmlFile)) {
                                    $this->info("    ✓ XML removido: {$nomeArquivo}");
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $erros++;
                        $this->warn("Erro em {$xmlFile}: ".$e->getMessage());
                    }
                }

                $progress = round(($index + 1) / $total * 100);
                $this->info("Progresso: {$progress}% ({$index}/{$total}) - Importados: {$importados}, Erros: {$erros}");

                // Pausa entre chunks para não sobrecarregar
                if ($index < $total - 1) {
                    sleep(1);
                }

                $chunk = [];
            }
        }

        $this->info('========================================');
        $this->info('Importação concluída!');
        $this->info("Total processados: {$total}");
        $this->info("Importados com sucesso: {$importados}");
        $this->info("Erros: {$erros}");

        return 0;
    }
}
