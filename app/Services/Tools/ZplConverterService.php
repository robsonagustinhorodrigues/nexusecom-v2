<?php

namespace App\Services\Tools;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZplConverterService
{
    private const LABELARY_API_URL = 'http://api.labelary.com/v1/printers';

    private const MAX_LABELS_PER_BATCH = 50;

    private const REQUEST_DELAY_MS = 500;

    private const MAX_RETRY_ATTEMPTS = 5;

    public function convertZplToPdf(string $zplData, int $dpmm, float $widthMm, float $heightMm): string
    {
        $zplLabels = $this->splitZplIntoLabels($zplData);
        $totalLabels = count($zplLabels);

        if ($totalLabels === 0) {
            throw new Exception('Nenhum dado ZPL válido (sem ^XA...^XZ) foi encontrado.');
        }

        $pdfBatches = [];
        $widthInches = $widthMm / 25.4;
        $heightInches = $heightMm / 25.4;

        $batches = array_chunk($zplLabels, self::MAX_LABELS_PER_BATCH);
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            $zplBatch = implode("\n", $batch);

            $url = sprintf(
                '%s/%ddpmm/labels/%gx%g/',
                self::LABELARY_API_URL,
                $dpmm,
                $widthInches,
                $heightInches
            );

            $response = $this->makeRequestWithRetry($url, $zplBatch, $batchIndex + 1, $totalBatches);

            if ($response->failed()) {
                Log::error('Erro na API Labelary', ['status' => $response->status(), 'response' => $response->body()]);
                throw new Exception('Erro na API Labelary: '.$response->body());
            }

            $pdfBatches[] = $response->body();

            if ($batchIndex < $totalBatches - 1) {
                usleep(self::REQUEST_DELAY_MS * 1000);
            }
        }

        if (count($pdfBatches) === 1) {
            return $pdfBatches[0];
        }

        return $pdfBatches[0];
    }

    private function makeRequestWithRetry(string $url, string $zplBatch, int $batchNumber, int $totalBatches)
    {
        $attempt = 0;

        while ($attempt < self::MAX_RETRY_ATTEMPTS) {
            $response = Http::withBody($zplBatch, 'application/x-www-form-urlencoded')
                ->withHeaders(['Accept' => 'application/pdf'])
                ->post($url);

            if ($response->status() !== 429) {
                return $response;
            }

            $attempt++;
            $retryAfter = $response->header('Retry-After');

            if ($retryAfter) {
                $waitSeconds = (int) $retryAfter;
                Log::warning("Rate limit Labelary. Aguardando {$waitSeconds}s.", ['attempt' => $attempt]);
                sleep($waitSeconds);
            } else {
                $waitSeconds = min($attempt * 2, 10);
                Log::warning("Rate limit Labelary (sem header). Aguardando {$waitSeconds}s.", ['attempt' => $attempt]);
                sleep($waitSeconds);
            }
        }

        return $response;
    }

    private function splitZplIntoLabels(string $zplData): array
    {
        $matches = [];
        preg_match_all('/(\^XA.*?\^XZ)/s', $zplData, $matches);

        return $matches[1] ?? [];
    }

    public function generateProductLabel(string $sku, string $nome, string $ean, float $preco): string
    {
        $zpl = '^XA';
        $zpl .= "^FO50,50^A0N,30,30^FD{$nome}^FS";
        $zpl .= "^FO50,90^A0N,25,25^FDSKU: {$sku}^FS";

        if ($ean) {
            $zpl .= '^FO50,130^BY3';
            $zpl .= "^BCN,80,Y,N,N^FD{$ean}^FS";
        }

        $zpl .= '^FO50,230^A0N,40,40^FDR$ '.number_format($preco, 2, ',', '.').'^FS';
        $zpl .= '^XZ';

        return $zpl;
    }
}
