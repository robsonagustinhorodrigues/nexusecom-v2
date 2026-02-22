<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\NfeItem;
use App\Models\NfeRecebida;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PharData;
use ZipArchive;

class FiscalService
{
    /**
     * Determina se a NF-e é de entrada ou saída baseado no CNPJ.
     */
    private function verificarTipoNfe(string $chave, int $empresaId): string
    {
        $empresa = \App\Models\Empresa::find($empresaId);
        if (!$empresa || empty($empresa->cnpj)) {
            return 'recebida'; // Default to received if no company found
        }
        
        // Extrai CNPJ emitente da chave NFe
        $cnpjEmitente = substr(preg_replace('/[^0-9]/', '', $chave), 6, 14);
        $cnpjEmpresa = preg_replace('/[^0-9]/', '', $empresa->cnpj);
        
        // Remove zeros à esquerda para comparação
        $cnpjEmitente = ltrim($cnpjEmitente, '0');
        $cnpjEmpresa = ltrim($cnpjEmpresa, '0');
        
        if (!empty($cnpjEmitente) && !empty($cnpjEmpresa) && $cnpjEmitente === $cnpjEmpresa) {
            return 'emitida';
        }
        
        return 'recebida';
    }

    /**
     * Gera um arquivo ZIP contendo os XMLs das chaves fornecidas.
     *
     * @param  array  $xmlPaths  Lista de caminhos relativos dos arquivos XML no storage.
     * @param  string  $prefix  Prefixo para o nome do arquivo ZIP.
     * @return string|null Retorna o caminho do arquivo ZIP gerado ou null em caso de erro.
     */
    public function generateZip(array $xmlPaths, string $prefix = 'export_nfe'): ?string
    {
        if (empty($xmlPaths)) {
            return null;
        }

        $zipFileName = $prefix.'_'.Str::random(8).'_'.time().'.zip';
        $tempPath = storage_path('app/temp/'.$zipFileName);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($tempPath, ZipArchive::CREATE) === true) {
            foreach ($xmlPaths as $path) {
                if ($path && Storage::exists($path)) {
                    $zip->addFromString(basename($path), Storage::get($path));
                }
            }
            $zip->close();

            return $tempPath;
        }

        return null;
    }

    /**
     * Importa um arquivo XML de NF-e.
     *
     * @throws \Exception
     */
    public function importXml($content, $empresaId, ?string $nomeArquivo = null)
    {
        try {
            $nomeArquivo = $nomeArquivo ?? 'arquivo_desconhecido.xml';

            $xml = simplexml_load_string($content);
            if (! $xml) {
                throw new \Exception("{$nomeArquivo}: XML Inválido ou mal formatado.");
            }

            // =====================================================
            // TRATAMENTO DE EVENTOS NF-e (procEventoNFe)
            // =====================================================
            // Registrar namespace para buscar eventos corretamente
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
            
            // Verificar se é evento usando xpath (necessário por causa do namespace)
            $eventos = $xml->xpath('//nfe:procEventoNFe');
            if (!$eventos) {
                $eventos = $xml->xpath('//procEventoNFe');
            }
            
            if ($eventos && count($eventos) > 0) {
                return $this->processarEventoNfe($eventos[0], $empresaId, $nomeArquivo);
            }

            // Verificar se é evento de inutilização (procInutNFe)
            $inutEventos = $xml->xpath('//nfe:procInutNFe');
            if (!$inutEventos) {
                $inutEventos = $xml->xpath('//procInutNFe');
            }
            
            if ($inutEventos && count($inutEventos) > 0) {
                $procInut = $xml->procInutNFe;
                $infInut = $procInut->infInut ?? null;
                if ($infInut) {
                    $chave = (string) ($infInut->chave ?? '');
                    $cStat = (string) ($procInut->retInut->infInut->cStat ?? '');
                    if ($cStat === '102' || $cStat === '107') {
                        // Inutilização aceita - marcar todas as NFe relacionadas
                        $this->marcarNfeComoInutilizada($empresaId, $chave);
                    }
                }
                return true;
            }

            // Lógica simplificada de extração (pode variar conforme versão da NF-e)
            $infNFe = $xml->NFe->infNFe ?? $xml->infNFe;
            if (! $infNFe) {
                throw new \Exception("{$nomeArquivo}: Estrutura de NF-e não encontrada no XML.");
            }

            // Verificar se é PROCINFE (retorno de inutilização ou denegada)
            $procNFe = $xml->procNFe ?? null;
            if ($procNFe) {
                $infNFe = $procNFe->NFe->infNFe ?? $procNFe->infNFe ?? $infNFe;
            }

            // Verificar status da NFe (autorizada, denegada, etc)
            $protNFe = $xml->protNFe ?? null;
            $statusNFe = 'aprovada';

            if ($protNFe) {
                $cStat = (string) ($protNFe->infProt->cStat ?? '');
                if ($cStat === '101' || $cStat === '151' || $cStat === '155') {
                    $statusNFe = 'cancelada';
                } elseif ($cStat === '110' || $cStat === '301' || $cStat === '302') {
                    $statusNFe = 'denegada';
                }
            }

            // Verificar se é evento de inutilização
            if (isset($xml->procInutNFe)) {
                $statusNFe = 'inutilizada';
            }

            // Validação de CNPJ (Emissor ou Destinatário)
            $empresa = \App\Models\Empresa::find($empresaId);
            if (! $empresa) {
                throw new \Exception("{$nomeArquivo}: Empresa não encontrada no sistema.");
            }

            $empresaCnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);
            $emitenteCnpj = preg_replace('/[^0-9]/', '', (string) ($infNFe->emit->CNPJ ?: $infNFe->emit->CPF));
            $destinatarioCnpj = preg_replace('/[^0-9]/', '', (string) ($infNFe->dest->CNPJ ?: $infNFe->dest->CPF));
            $destinatarioNome = (string) ($infNFe->dest->xNome ?? '');

            if ($empresaCnpj !== $emitenteCnpj && $empresaCnpj !== $destinatarioCnpj) {
                \Log::warning("Tentativa de importação de XML que não pertence à empresa {$empresa->nome}. XML Chave: ".($infNFe->attributes()->Id ?? 'Desconhecida'));

                throw new \Exception("{$nomeArquivo}: CNPJ não corresponde à empresa. Emitente: {$emitenteCnpj}, Destinatário: {$destinatarioCnpj}, Empresa: {$empresaCnpj}");
            }

            $chave = (string) $infNFe->attributes()->Id;
            $chave = preg_replace('/[^0-9]/', '', $chave);

            // Verificar se esta NF já foi devolvida anteriormente
            $jaFoiDevolvida = $this->verificarSeJaFoiDevolvida($empresaId, $chave);

            $numero = (string) $infNFe->ide->nNF;
            $serie = (string) ($infNFe->ide->serie ?? '1');
            $valor = (float) $infNFe->total->ICMSTot->vNF;
            $dataEmissao = (string) $infNFe->ide->dhEmi ?: (string) $infNFe->ide->dEmi;

            // Verificar se é NF de devolução (finNFe = 4)
            $finNFe = (int) ($infNFe->ide->finNFe ?? 1);
            $isDevolucao = ($finNFe === 4);

            // Dados da NF de origem para devolução
            $nfeOrigemChave = null;
            $nfeOrigemNumero = null;
            $nfeOrigemSerie = null;

            if ($isDevolucao && isset($infNFe->devol->NFref)) {
                foreach ($infNFe->devol->NFref as $nfRef) {
                    if (isset($nfRef->refNF)) {
                        // NF referenciada normal
                        $nfeOrigemChave = (string) ($nfRef->refNF->chave ?? null);
                        $nfeOrigemNumero = (string) ($nfRef->refNF->nNF ?? null);
                        $nfeOrigemSerie = (string) ($nfRef->refNF->serie ?? null);
                    } elseif (isset($nfRef->refCTe)) {
                        // Referência CT-e, não é NF
                    } elseif (isset($nfRef->refECF)) {
                        // Referência ECF, não é NF
                    }
                    if ($nfeOrigemChave) {
                        break;
                    }
                }
            }

            // Dados do Emitente
            $emitenteNome = (string) $infNFe->emit->xNome;

            // Se a NF já foi devolvida anteriormente, usa os dados da devolução
            $jaDevolvida = $jaFoiDevolvida !== null;

            // Determina se é nota de entrada ou saída
            $tipoNfe = $this->verificarTipoNfe($chave, $empresaId);

            // Salva ou Atualiza
            if ($tipoNfe === 'emitida') {
                NfeEmitida::updateOrCreate(
                    ['chave' => $chave, 'empresa_id' => $empresaId],
                    [
                        'numero' => $numero,
                        'serie' => $serie,
                        'valor_total' => $valor,
                        'data_emissao' => $dataEmissao ? \Carbon\Carbon::parse($dataEmissao) : now(),
                        'emitente_cnpj' => $emitenteCnpj,
                        'emitente_nome' => $emitenteNome,
                        'cliente_cnpj' => $destinatarioCnpj,
                        'cliente_nome' => $destinatarioNome,
                        'status' => $statusNFe,
                        'xml_path' => $this->saveXml($content, $chave, $empresaId, $dataEmissao),
                    ]
                );
            } else {
                NfeRecebida::updateOrCreate(
                    ['chave' => $chave, 'empresa_id' => $empresaId],
                    [
                        'numero' => $numero,
                        'serie' => $serie,
                        'valor_total' => $valor,
                        'data_emissao' => $dataEmissao ? \Carbon\Carbon::parse($dataEmissao) : now(),
                        'emitente_cnpj' => $emitenteCnpj,
                        'emitente_nome' => $emitenteNome,
                        'cliente_cnpj' => $destinatarioCnpj,
                        'cliente_nome' => $destinatarioNome,
                        'status_manifestacao' => 'sem_manifesto',
                        'status_nfe' => $statusNFe,
                        'xml_path' => $this->saveXml($content, $chave, $empresaId, $dataEmissao),
                        'devolucao' => $isDevolucao || $jaDevolvida,
                        'nfe_devolvida_chave' => $jaDevolvida ? ($jaFoiDevolvida['chave_devolucao'] ?? null) : $nfeOrigemChave,
                        'nfe_devolvida_numero' => $jaDevolvida ? ($jaFoiDevolvida['numero_devolucao'] ?? null) : $nfeOrigemNumero,
                        'nfe_devolvida_serie' => $jaDevolvida ? ($jaFoiDevolvida['serie_devolucao'] ?? null) : $nfeOrigemSerie,
                    ]
                );
            }

            // Se é devolução, marcar a NF de origem como devolvida
            if ($isDevolucao && $nfeOrigemChave) {
                $this->marcarNfeOrigemDevolvida($empresaId, $nfeOrigemChave, $chave, $numero, $serie);
            }

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Importa um arquivo ZIP ou 7Z contendo múltiplos XMLs (incluindo subpastas).
     */
    public function importZip($zipPath, $empresaId)
    {
        $count = 0;
        $extension = pathinfo($zipPath, PATHINFO_EXTENSION);

        try {
            if (strtolower($extension) === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($zipPath) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (stripos($filename, '.xml') !== false && ! is_dir($filename)) {
                            $content = $zip->getFromIndex($i);
                            if ($this->importXml($content, $empresaId)) {
                                $count++;
                            }
                        }
                    }
                    $zip->close();
                }
            } elseif (strtolower($extension) === '7z') {
                $count = $this->extract7zFiles($zipPath, $empresaId);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao importar arquivo compactado: '.$e->getMessage());
        }

        return $count;
    }

    /**
     * Extrai arquivos de um 7z usando comandos do sistema ou PharData.
     */
    protected function extract7zFiles(string $zipPath, int $empresaId): int
    {
        $count = 0;
        $tempDir = sys_get_temp_dir().'/nfe_7z_'.uniqid();

        try {
            @mkdir($tempDir, 0755, true);

            $sevenZipCommand = $this->get7zCommand();

            if ($sevenZipCommand) {
                $command = "{$sevenZipCommand} x -y -o\"{$tempDir}\" \"{$zipPath}\" 2>/dev/null";
                exec($command, $output, $return);

                if ($return !== 0) {
                    \Log::warning('7z command failed, trying PharData: '.implode("\n", $output));
                }
            }

            if (! is_dir($tempDir) || ! (new \FilesystemIterator($tempDir))->valid()) {
                $phar = new \PharData($zipPath);
                $phar->extractTo($tempDir, null, true);
            }

            $this->processExtractedDirectory($tempDir, $empresaId, $count);

        } catch (\Exception $e) {
            \Log::error('Erro ao descompactar 7z com PharData: '.$e->getMessage());

            try {
                $phar = new \PharData($zipPath);
                $tempDir = sys_get_temp_dir().'/nfe_7z_'.uniqid();
                @mkdir($tempDir, 0755, true);
                $phar->extractTo($tempDir, null, true);
                $this->processExtractedDirectory($tempDir, $empresaId, $count);
            } catch (\Exception $e2) {
                \Log::error('Erro ao descompactar 7z (fallback): '.$e2->getMessage());
            }
        } finally {
            if (is_dir($tempDir)) {
                array_map('unlink', glob("{$tempDir}/*.*"));
                @rmdir($tempDir);
            }
        }

        return $count;
    }

    /**
     * Detecta qual comando 7z está disponível.
     */
    protected function get7zCommand(): ?string
    {
        $commands = ['7z', '7za', 'p7zip'];

        foreach ($commands as $cmd) {
            exec("which {$cmd} 2>/dev/null", $output, $return);
            if ($return === 0 && ! empty($output)) {
                return trim($output[0]);
            }
        }

        return null;
    }

    /**
     * Processa diretório extraído recursivamente.
     */
    protected function processExtractedDirectory(string $dir, int $empresaId, int &$count): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'xml') {
                try {
                    $content = file_get_contents($file->getPathname());
                    if ($this->importXml($content, $empresaId)) {
                        $count++;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Erro ao importar XML do 7z: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Conta quantos arquivos XML existem em um ZIP ou 7Z (incluindo subpastas).
     */
    public function extractZipCount(string $zipPath): int
    {
        $maxSize = 100 * 1024 * 1024;

        if (! file_exists($zipPath) || filesize($zipPath) > $maxSize) {
            return 0;
        }

        $count = 0;
        $extension = pathinfo($zipPath, PATHINFO_EXTENSION);

        try {
            if (strtolower($extension) === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($zipPath) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (stripos($filename, '.xml') !== false && ! is_dir($filename)) {
                            $count++;
                        }
                    }
                    $zip->close();
                }
            } elseif (strtolower($extension) === '7z') {
                $count = $this->count7zFiles($zipPath);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao contar XMLs no arquivo: '.$e->getMessage());
        }

        return $count;
    }

    /**
     * Conta arquivos XML em um arquivo 7z.
     */
    protected function count7zFiles(string $zipPath): int
    {
        $count = 0;
        $tempDir = sys_get_temp_dir().'/nfe_count_'.uniqid();

        try {
            @mkdir($tempDir, 0755, true);

            $sevenZipCommand = $this->get7zCommand();

            if ($sevenZipCommand) {
                $command = "{$sevenZipCommand} x -y -o\"{$tempDir}\" \"{$zipPath}\" 2>/dev/null";
                exec($command, $output, $return);

                if ($return === 0) {
                    $count = count(glob("{$tempDir}/**/*.xml", GLOB_BRACE));
                }
            }

            if ($count === 0) {
                $phar = new \PharData($zipPath);
                $phar->extractTo($tempDir, null, true);
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && strtolower($file->getExtension()) === 'xml') {
                        $count++;
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('Erro ao contar XMLs no 7z: '.$e->getMessage());
        } finally {
            if (is_dir($tempDir)) {
                array_map('unlink', glob("{$tempDir}/*.*"));
                @rmdir($tempDir);
            }
        }

        return $count;
    }

    /**
     * Importa um arquivo específico de um ZIP pelo índice (incluindo subpastas).
     *
     * @throws \Exception
     */
    public function importZipSingle(string $zipPath, int $empresaId, int $index, ?string $nomeArquivo = null): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) === true) {
            if ($index < $zip->numFiles) {
                $filename = $zip->getNameIndex($index);
                $nomeArquivo = $nomeArquivo ?? basename($filename);
                if (stripos($filename, '.xml') !== false && ! is_dir($filename)) {
                    $content = $zip->getFromIndex($index);
                    $zip->close();

                    return $this->importXml($content, $empresaId, $nomeArquivo);
                }
            }
            $zip->close();
        }

        throw new \Exception("{$nomeArquivo}: Arquivo não encontrado no ZIP.");
    }

    /**
     * Importa arquivos XML de um arquivo 7z extraído.
     *
     * @throws \Exception
     */
    public function import7zSingle(string $zipPath, int $empresaId, int $index, ?string $nomeArquivo = null): bool
    {
        $tempDir = sys_get_temp_dir().'/nfe_import_7z_'.uniqid();

        try {
            @mkdir($tempDir, 0755, true);

            $extension = pathinfo($zipPath, PATHINFO_EXTENSION);
            if (strtolower($extension) === '7z') {
                $sevenZipCommand = $this->get7zCommand();

                if ($sevenZipCommand) {
                    $command = "{$sevenZipCommand} x -y -o\"{$tempDir}\" \"{$zipPath}\" 2>/dev/null";
                    exec($command, $output, $return);

                    if ($return !== 0) {
                        $phar = new \PharData($zipPath);
                        $phar->extractTo($tempDir, null, true);
                    }
                } else {
                    $phar = new \PharData($zipPath);
                    $phar->extractTo($tempDir, null, true);
                }
            } else {
                return $this->importZipSingle($zipPath, $empresaId, $index, $nomeArquivo);
            }

            $xmlFiles = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'xml') {
                    $xmlFiles[] = $file->getPathname();
                }
            }

            sort($xmlFiles);

            if (! isset($xmlFiles[$index])) {
                throw new \Exception("{$nomeArquivo}: Arquivo não encontrado no 7z (índice: {$index}).");
            }

            $content = file_get_contents($xmlFiles[$index]);
            $fileName = basename($xmlFiles[$index]);

            return $this->importXml($content, $empresaId, $fileName);

        } finally {
            if (is_dir($tempDir)) {
                array_map('unlink', glob("{$tempDir}/*.*"));
                @rmdir($tempDir);
            }
        }
    }

    /**
     * Retorna informações de um XML sem salvar.
     */
    public function previewXml($content): ?array
    {
        try {
            $xml = simplexml_load_string($content);
            if (! $xml) {
                return null;
            }

            $infNFe = $xml->NFe->infNFe ?? $xml->infNFe;
            if (! $infNFe) {
                return null;
            }

            $chave = (string) $infNFe->attributes()->Id;
            $chave = preg_replace('/[^0-9]/', '', $chave);

            return [
                'chave' => $chave,
                'numero' => (string) ($infNFe->ide->nNF ?? 'N/D'),
                'valor' => (float) ($infNFe->total->ICMSTot->vNF ?? 0),
                'emitente_nome' => (string) ($infNFe->emit->xNome ?? 'N/D'),
                'emitente_cnpj' => (string) ($infNFe->emit->CNPJ ?? $infNFe->emit->CPF ?? 'N/D'),
                'data_emissao' => (string) ($infNFe->ide->dhEmi ?? $infNFe->ide->dEmi ?? now()),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function saveXml($content, $chave, ?int $empresaId = null, ?string $dataEmissao = null)
    {
        // Get empresa info for path
        $grupoId = 'default';
        $cnpj = 'default';
        
        if ($empresaId) {
            $empresa = Empresa::find($empresaId);
            if ($empresa) {
                $grupoId = $empresa->grupo_id ?? 'default';
                $cnpj = $empresa->cnpj ?? 'default';
            }
        }
        
        // Extract year and month from emission date
        $ano = date('Y');
        $mes = date('m');
        
        if ($dataEmissao) {
            try {
                $date = \Carbon\Carbon::parse($dataEmissao);
                $ano = $date->format('Y');
                $mes = $date->format('m');
            } catch (\Exception $e) {
                // Use current date if parsing fails
            }
        }
        
        // Path: grupo_id/cnpj/ano/mes/chave.xml
        $path = "nfes/{$grupoId}/{$cnpj}/{$ano}/{$mes}/{$chave}.xml";
        Storage::put($path, $content);

        return $path;
    }

    /**
     * Marca a NF de origem como devolvida.
     */
    private function marcarNfeOrigemDevolvida(int $empresaId, string $chaveOrigem, string $chaveDevolucao, string $numeroDevolucao, string $serieDevolucao): void
    {
        // Primeiro, tenta encontrar na NFe Emitida (nós emitimos a NF original)
        $nfeEmitida = NfeEmitida::where('empresa_id', $empresaId)
            ->where('chave', $chaveOrigem)
            ->first();

        if ($nfeEmitida) {
            $nfeEmitida->update([
                'devolvida' => true,
                'nfe_devolucao_chave' => $chaveDevolucao,
                'nfe_devolucao_numero' => $numeroDevolucao,
                'nfe_devolucao_serie' => $serieDevolucao,
            ]);

            return;
        }

        // Segundo, tenta encontrar na NFe Recebida (recebemos a NF original)
        $nfeRecebida = NfeRecebida::where('empresa_id', $empresaId)
            ->where('chave', $chaveOrigem)
            ->first();

        if ($nfeRecebida) {
            $nfeRecebida->update([
                'devolvida' => true,
                'nfe_devolucao_chave' => $chaveDevolucao,
                'nfe_devolucao_numero' => $numeroDevolucao,
                'nfe_devolucao_serie' => $serieDevolucao,
            ]);
        }
    }

    /**
     * Verifica se uma NF já foi devolvida anteriormente.
     */
    private function verificarSeJaFoiDevolvida(int $empresaId, string $chave): ?array
    {
        // Verifica na NFe Emitida
        $nfeEmitida = NfeEmitida::where('empresa_id', $empresaId)
            ->where('chave', $chave)
            ->where('devolvida', true)
            ->first(['nfe_devolucao_chave', 'nfe_devolucao_numero', 'nfe_devolucao_serie']);

        if ($nfeEmitida) {
            return [
                'tabela' => 'nfe_emitidas',
                'chave_devolucao' => $nfeEmitida->nfe_devolucao_chave,
                'numero_devolucao' => $nfeEmitida->nfe_devolucao_numero,
                'serie_devolucao' => $nfeEmitida->nfe_devolucao_serie,
            ];
        }

        // Verifica na NFe Recebida
        $nfeRecebida = NfeRecebida::where('empresa_id', $empresaId)
            ->where('chave', $chave)
            ->where('devolucao', true)
            ->first(['nfe_devolvida_chave', 'nfe_devolvida_numero', 'nfe_devolvida_serie']);

        if ($nfeRecebida) {
            return [
                'tabela' => 'nfe_recebidas',
                'chave_devolucao' => $nfeRecebida->nfe_devolvida_chave,
                'numero_devolucao' => $nfeRecebida->nfe_devolvida_numero,
                'serie_devolucao' => $nfeRecebida->nfe_devolvida_serie,
            ];
        }

        return null;
    }

    /**
     * Processa os itens de um XML de NF-e e salva no banco.
     */
    public function processarItensNfe(string $content, ?int $nfeEmitidaId = null, ?int $nfeRecebidaId = null): int
    {
        $itensSalvos = 0;

        try {
            $xml = simplexml_load_string($content);
            if (! $xml) {
                return 0;
            }

            $infNFe = $xml->NFe->infNFe ?? $xml->infNFe;
            if (! $infNFe) {
                return 0;
            }

            $procNFe = $xml->procNFe ?? null;
            if ($procNFe) {
                $infNFe = $procNFe->NFe->infNFe ?? $procNFe->infNFe ?? $infNFe;
            }

            if (! isset($infNFe->det) || empty($infNFe->det)) {
                return 0;
            }

            foreach ($infNFe->det as $index => $det) {
                $numeroItem = (int) ($det->attributes()->nItem ?? $index + 1);

                $produto = $det->prod ?? null;
                $imposto = $det->imposto ?? null;

                $ncm = null;
                $cfop = null;
                if ($produto) {
                    $ncm = (string) ($produto->NCM ?? $produto->cProd);
                    $cfop = (string) ($produto->CFOP ?? null);
                }

                $tributado = $this->isTributado($ncm, $cfop);

                $itemData = [
                    'nfe_emitida_id' => $nfeEmitidaId,
                    'nfe_recebida_id' => $nfeRecebidaId,
                    'numero_item' => $numeroItem,
                    'codigo_produto' => $produto ? (string) ($produto->cProd ?? null) : null,
                    'gtin' => $produto ? (string) ($produto->cEAN ?? null) : null,
                    'descricao' => $produto ? (string) ($produto->xProd ?? 'Produto sem descrição') : 'Produto sem descrição',
                    'ncm' => $ncm,
                    'cfop' => $cfop,
                    'unidade' => $produto ? (string) ($produto->uCom ?? null) : null,
                    'quantidade' => $produto ? (float) ($produto->qCom ?? 0) : 0,
                    'valor_unitario' => $produto ? (float) ($produto->vUnCom ?? 0) : 0,
                    'valor_total' => $produto ? (float) ($produto->vProd ?? 0) : 0,
                    'valor_desconto' => $produto ? (float) ($produto->vDesc ?? 0) : 0,
                    'valor_frete' => $produto ? (float) ($produto->vFrete ?? 0) : 0,
                    'valor_seguro' => $produto ? (float) ($produto->vSeg ?? 0) : 0,
                    'valor_outros' => $produto ? (float) ($produto->vOutro ?? 0) : 0,
                    'tributado' => $tributado,
                ];

                if ($imposto) {
                    $icms = $imposto->ICMS ?? null;
                    if ($icms) {
                        foreach ($icms as $icmsItem) {
                            $itemData['base_calculo_icms'] = (float) ($icmsItem->vBC ?? 0);
                            $itemData['aliquota_icms'] = (float) ($icmsItem->pICMS ?? 0);
                            $itemData['valor_icms'] = (float) ($icmsItem->vICMS ?? 0);
                            $itemData['base_calculo_icms_st'] = (float) ($icmsItem->vBCST ?? 0);
                            $itemData['aliquota_icms_st'] = (float) ($icmsItem->pICMSST ?? 0);
                            $itemData['valor_icms_st'] = (float) ($icmsItem->vICMSST ?? 0);
                            break;
                        }
                    }

                    $pis = $imposto->PIS ?? null;
                    if ($pis && isset($pis->PISItem)) {
                        foreach ($pis as $pisItem) {
                            if (isset($pisItem->vBC)) {
                                $itemData['aliquota_pis'] = (float) ($pisItem->pPIS ?? 0);
                                $itemData['valor_pis'] = (float) ($pisItem->vPIS ?? 0);
                                break;
                            }
                        }
                    }

                    $cofins = $imposto->COFINS ?? null;
                    if ($cofins && isset($cofins->COFINSItem)) {
                        foreach ($cofins as $cofinsItem) {
                            if (isset($cofinsItem->vBC)) {
                                $itemData['aliquota_cofins'] = (float) ($cofinsItem->pCOFINS ?? 0);
                                $itemData['valor_cofins'] = (float) ($cofinsItem->vCOFINS ?? 0);
                                break;
                            }
                        }
                    }

                    $iss = $imposto->ISS ?? null;
                    if ($iss) {
                        $itemData['aliquota_iss'] = (float) ($iss->vBC ?? 0);
                        $itemData['valor_iss'] = (float) ($iss->vISS ?? 0);
                    }
                }

                if ($det->infAdProd) {
                    $itemData['informacoes_adicionais'] = (string) $det->infAdProd;
                }

                NfeItem::create($itemData);
                
                // Auto-associate product by SKU
                $item = NfeItem::latest()->first();
                if ($item) {
                    $item->associateProduct($empresa->grupo_id);
                }
                
                $itensSalvos++;
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao processar itens da NF-e: '.$e->getMessage());
        }

        return $itensSalvos;
    }

    /**
     * Verifica se o item é tributado com base no NCM e CFOP.
     */
    private function isTributado(?string $ncm, ?string $cfop): bool
    {
        if (! $ncm || ! $cfop) {
            return true;
        }

        $ncm4 = substr($ncm, 0, 4);

        $ncmIsentos = [
            '0101', '0102', '0103', '0201', '0202', '0203', '0204', '0205', '0206', '0208', '0210',
            '0301', '0302', '0303', '0304', '0305', '0306', '0307',
            '0401', '0402', '0403', '0405', '0406',
            '0701', '0702', '0703', '0704', '0705', '0706', '0707', '0708', '0709',
            '0801', '0802', '0803', '0804', '0805', '0806', '0808', '0809',
            '0901', '0902', '0903',
            '1001', '1002', '1003', '1004', '1005', '1006', '1007', '1008',
            '1101', '1102', '1103', '1104', '1105',
            '1201', '1202', '1203', '1204', '1205', '1206', '1207', '1208',
            '1507', '1508', '1509', '1510', '1512',
            '1604', '1605',
        ];

        if (in_array($ncm4, $ncmIsentos)) {
            return false;
        }

        if (in_array($cfop, ['1401', '1403', '1406', '1407', '2401', '2403', '2404', '2405', '2406'])) {
            return false;
        }

        return true;
    }

    /**
     * Associa itens de NF-e com produtos do sistema.
     */
    public function associarItensComProdutos(int $empresaId): array
    {
        $resultado = [
            'associados' => 0,
            'nao_encontrados' => 0,
            'erros' => [],
        ];

        $itens = NfeItem::whereHas('nfeEmitida', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })->orWhereHas('nfeRecebida', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })->whereNull('product_id')->get();

        foreach ($itens as $item) {
            try {
                $produto = $this->buscarProdutoPorSkuOuGtin($empresaId, $item->codigo_produto, $item->gtin);

                if ($produto) {
                    $item->update(['product_id' => $produto->id]);
                    $resultado['associados']++;
                } else {
                    $resultado['nao_encontrados']++;
                }
            } catch (\Exception $e) {
                $resultado['erros'][] = "Item {$item->id}: ".$e->getMessage();
            }
        }

        return $resultado;
    }

    /**
     * Busca produto por SKU ou GTIN.
     */
    private function buscarProdutoPorSkuOuGtin(int $empresaId, ?string $sku, ?string $gtin): ?\App\Models\Product
    {
        if (! $sku && ! $gtin) {
            return null;
        }

        if ($sku) {
            $produtoSku = \App\Models\ProductSku::where('grupo_id', function ($q) use ($empresaId) {
                $q->select('grupo_id')->from('empresas')->where('id', $empresaId);
            })->where('sku', $sku)->first();

            if ($produtoSku) {
                return $produtoSku->product;
            }

            $produto = \App\Models\Product::where('empresa_id', $empresaId)
                ->where('ean', $sku)
                ->first();

            if ($produto) {
                return $produto;
            }
        }

        if ($gtin) {
            $produtoSku = \App\Models\ProductSku::where('grupo_id', function ($q) use ($empresaId) {
                $q->select('grupo_id')->from('empresas')->where('id', $empresaId);
            })->where('gtin', $gtin)->first();

            if ($produtoSku) {
                return $produtoSku->product;
            }

            $produto = \App\Models\Product::where('empresa_id', $empresaId)
                ->where('ean', $gtin)
                ->first();

            if ($produto) {
                return $produto;
            }
        }

        return null;
    }

    /**
     * Processa evento de NF-e (procEventoNFe)
     * Tipos de eventos:
     * - 110111: Cancelamento
     * - 110110: Carta de Correção
     * - 110115: Manifestação do Destinatário (CIENCIA, CONFIRMACAO, DESCONHECIMENTO, NAO_REALIZADA)
     * - 210200: Envio de XML de Emitente para Destinatário
     */
    protected function processarEventoNfe($procEvento, int $empresaId, string $nomeArquivo): bool
    {
        try {
            // Registrar namespace para buscar elementos do evento
            $procEvento->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
            
            // Extrair dados do evento usando xpath
            $infEventos = $procEvento->xpath('.//nfe:infEvento');
            if (!$infEventos || count($infEventos) === 0) {
                $infEventos = $procEvento->xpath('.//infEvento');
            }
            
            if (!$infEventos || count($infEventos) === 0) {
                throw new \Exception("{$nomeArquivo}: Estrutura de evento inválida.");
            }
            
            $infEvento = $infEventos[0];
            
            $chaveNFe = (string) ($infEvento->chNFe ?? '');
            $tipoEvento = (string) ($infEvento->tpEvento ?? '');
            $protocolo = (string) ($infEvento->nProt ?? '');
            $xMotivo = (string) ($infEvento->xMotivo ?? '');

            if (empty($chaveNFe)) {
                throw new \Exception("{$nomeArquivo}: Chave da NF-e não encontrada no evento.");
            }

            \Log::info("Processando evento {$tipoEvento} para NF-e {$chaveNFe}");

            // Buscar a NF-e no banco
            $nfeEmitida = \App\Models\NfeEmitida::where('chave', $chaveNFe)
                ->where('empresa_id', $empresaId)
                ->first();

            $nfeRecebida = \App\Models\NfeRecebida::where('chave', $chaveNFe)
                ->where('empresa_id', $empresaId)
                ->first();

            // Processar conforme tipo de evento
            switch ($tipoEvento) {
                case '110111': // Cancelamento
                    if ($nfeEmitida) {
                        $nfeEmitida->update([
                            'status' => 'cancelada',
                            'protocolo_cancelamento' => $protocolo,
                            'motivo_cancelamento' => $xMotivo,
                        ]);
                        \Log::info("NF-e emitida {$chaveNFe} marcada como CANCELADA");
                    }
                    if ($nfeRecebida) {
                        $nfeRecebida->update([
                            'status_nfe' => 'cancelada',
                            'protocolo_cancelamento' => $protocolo,
                            'motivo_cancelamento' => $xMotivo,
                        ]);
                        \Log::info("NF-e recebida {$chaveNFe} marcada como CANCELADA");
                    }
                    break;

                case '110110': // Carta de Correção
                    // Salvar evento deCCE
                    $this->salvarEventoNfe($nfeRecebida?->id, $tipoEvento, $protocolo, $xMotivo, $procEvento);
                    \Log::info("CCE registrada para NF-e {$chaveNFe}");
                    break;

                case '210200': // Envio de XML do Emitente para Destinatário
                case '210210': // Recebimento pelo Destinatário
                    // Não faz nada, apenas registra
                    break;

                default:
                    // Outros eventos - salvar para referência
                    $this->salvarEventoNfe($nfeRecebida?->id, $tipoEvento, $protocolo, $xMotivo, $procEvento);
                    \Log::info("Evento {$tipoEvento} registrado para NF-e {$chaveNFe}");
                    break;
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception("{$nomeArquivo}: Erro ao processar evento - " . $e->getMessage());
        }
    }

    /**
     * Salva evento no banco de dados
     */
    protected function salvarEventoNfe(?int $nfeRecebidaId, string $tipoEvento, string $protocolo, string $xMotivo, $procEvento)
    {
        if (! $nfeRecebidaId) {
            return;
        }

        try {
            \App\Models\NfeEvento::create([
                'nfe_recebida_id' => $nfeRecebidaId,
                'tipo_evento' => $tipoEvento,
                'protocolo' => $protocolo,
                'x_motivo' => $xMotivo,
                'payload_envio' => json_decode(json_encode($procEvento->evento->infEvento), true) ?? [],
                'payload_retorno' => json_decode(json_encode($procEvento->retEvento->infEvento), true) ?? [],
            ]);
        } catch (\Exception $e) {
            \Log::warning("Erro ao salvar evento NFe: " . $e->getMessage());
        }
    }

    /**
     * Marca NF-e como inutilizada
     */
    protected function marcarNfeComoInutilizada(int $empresaId, string $chaveInutilizacao)
    {
        // A chave de inutilização contém o range de números inutilizados
        // Ex: 3520xx0000000000000000000000000000000000
        // Precisa buscar as NFes que foram inutilizadas
        
        $nfeEmitidas = \App\Models\NfeEmitida::where('empresa_id', $empresaId)
            ->where('chave', 'like', substr($chaveInutilizacao, 0, 20) . '%')
            ->get();

        foreach ($nfeEmitidas as $nfe) {
            $nfe->update(['status' => 'inutilizada']);
        }

        \Log::info("NF-e(s) marcada(s) como INUTILIZADA(S) para chave base {$chaveInutilizacao}");
    }
}
