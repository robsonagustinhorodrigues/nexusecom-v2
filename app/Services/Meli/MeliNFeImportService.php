<?php

namespace App\Services\Meli;

use App\Jobs\ImportarNFeMeliJob;
use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class MeliNFeImportService
{
    /**
     * Importa notas fiscais do Mercado Livre por período
     */
    public function execute(Empresa $empresa, string $dataInicial, string $dataFinal): array
    {
        $importErrors = [];
        $imported = [];

        // Buscar integração do Mercado Livre
        $integracao = $empresa->integracoes()->where('marketplace', 'mercadolivre')->first();
        
        if (!$integracao) {
            return ['imported' => [], 'errors' => ['Integração do Mercado Livre não encontrada.']];
        }

        if ($integracao->isExpired()) {
            return ['imported' => [], 'errors' => ['Token do Mercado Livre expirado. Reconecte a integração.']];
        }

        $meliId = $integracao->external_user_id;
        $accessToken = $integracao->access_token;
        
        $filePath = null;
        
        try {
            // 1. Baixa o ZIP com as notas
            $params = [
                'start' => date('Ymd', strtotime($dataInicial)),
                'end' => date('Ymd', strtotime($dataFinal)),
                'return' => 'all',
                'full' => 'all',
                'sale' => 'all',
                'simple_folder' => 'true',
                'file_types' => 'xml',
            ];

            // Download do ZIP
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->timeout(600)
                ->get("https://api.mercadolibre.com/users/{$meliId}/invoices/sites/MLB/batch_request/period/stream", $params);

            if ($response->failed()) {
                return ['imported' => [], 'errors' => ['Erro ao baixar notas do Mercado Livre: ' . $response->body()]];
            }

            // Salvar o arquivo
            $fileName = 'meli_invoices_' . time() . '_' . uniqid() . '.zip';
            $fullPath = storage_path('app/temp/' . $fileName);
            
            // Criar diretório se não existir
            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            file_put_contents($fullPath, $response->body());

            // 2. Abre o ZIP
            $zip = new ZipArchive();
            if ($zip->open($fullPath) !== true) {
                return ['imported' => [], 'errors' => ['Não foi possível abrir o arquivo ZIP.']];
            }

            // 3. Itera sobre os arquivos dentro do ZIP
            $processed = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileNameXml = $zip->getNameIndex($i);

                // Ignora pastas ou arquivos que não sejam XML
                if (substr($fileNameXml, -1) === '/' || pathinfo($fileNameXml, PATHINFO_EXTENSION) !== 'xml') {
                    continue;
                }

                // 4. Lê o conteúdo do XML
                $xmlContent = $zip->getFromIndex($i);

                if ($xmlContent) {
                    // 5. Despacha para a Fila (QUEUE)
                    ImportarNFeMeliJob::dispatch($empresa, $xmlContent);
                    $processed++;
                }
            }

            $zip->close();
            
            // Remove o arquivo ZIP temporário
            @unlink($fullPath);

            return [
                'imported' => $processed, 
                'errors' => []
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao importar NF-es do Mercado Livre: ' . $e->getMessage());
            return ['imported' => [], 'errors' => ['Erro: ' . $e->getMessage()]];
        }
    }
}
