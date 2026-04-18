<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Tools;

class SefazEngine
{
    /**
     * Determina se a NF-e é de entrada ou saída baseado no CNPJ.
     * Se o CNPJ emitente = CNPJ da empresa → SAÍDA (nfe_emitida)
     * Caso contrário → ENTRADA (nfe_recebida)
     */
    private function verificarTipoNfe(string $chave, Empresa $empresa, ?int $tpNF = null, ?string $emitCnpj = null, ?string $destCnpj = null): string
    {
        $cnpjEmpresa = ltrim(preg_replace('/[^0-9]/', '', $empresa->cnpj), '0');
        
        // Se temos o CNPJ do emitente explicitamente, usamos ele, senão extraímos da chave
        $cnpjEmitente = ltrim(preg_replace('/[^0-9]/', '', $emitCnpj ?: substr(preg_replace('/[^0-9]/', '', $chave), 6, 14)), '0');
        
        // Se o emitente é a própria empresa, é uma nota EMITIDA
        if (!empty($cnpjEmitente) && !empty($cnpjEmpresa) && $cnpjEmitente === $cnpjEmpresa) {
            return 'emitida';
        }
        
        // Se o destinatário é a empresa e o emitente é outro, é RECEBIDA
        if ($destCnpj) {
            $cnpjDest = ltrim(preg_replace('/[^0-9]/', '', $destCnpj), '0');
            if ($cnpjDest === $cnpjEmpresa && $cnpjEmitente !== $cnpjEmpresa) {
                return 'recebida';
            }
        }

        // Fallback para tpNF: se não somos o emitente e o tipo é Entrada (0), é recebida
        if ($tpNF === 0 && $cnpjEmitente !== $cnpjEmpresa) {
            return 'recebida';
        }
        
        return 'recebida';
    }

    /**
     * Busca novas NF-es para uma empresa usando Manifestação do Destinatário.
     */
    public function buscarNovasNotas(Empresa $empresa)
    {
        if (! $empresa->certificado_a1_path || ! $empresa->certificado_senha) {
            Log::warning("Empresa {$empresa->nome} sem certificado configurado para busca SEFAZ.");
            throw new \Exception('Certificado Digital não configurado para esta empresa.');
        }

        $intervaloHoras = $empresa->sefaz_intervalo_horas ?? 6;
        $intervaloMinutos = $intervaloHoras * 60;

        // Bloqueio baseado no intervalo configurado
        if ($empresa->last_sefaz_query_at && $empresa->last_sefaz_query_at->diffInMinutes(now()) < $intervaloMinutos) {
            $proximaConsulta = $empresa->last_sefaz_query_at->addMinutes($intervaloMinutos)->format('H:i');
            throw new \Exception("Aguarde o intervalo de {$intervaloHoras} horas entre consultas. Próxima consulta permitida às {$proximaConsulta}.");
        }

        Log::info("Iniciando busca SEFAZ para {$empresa->nome} [CNPJ: {$empresa->cnpj}] a partir de NSU: {$empresa->last_nsu}");

        try {
            $certificate = $this->getCertificate($empresa);
            $config = $this->getConfig($empresa);
            $tools = new Tools(json_encode($config), $certificate);
            $tools->model('55');

            // 1. Manifestar notas pendentes caso auto_ciencia esteja ativo
            $this->manifestarPendentes($empresa, $tools);

            // 2. Chamada DistDFe (Ultimo NSU)
            $lastNsu = $empresa->last_nsu ?? 0;
            $resp = $tools->sefazDistDFe($lastNsu);

            // 3. Processamento do Retorno
            $result = $this->processResponse($resp, $empresa);

            // Atualiza NSU e Horário
            $empresa->update([
                'last_nsu' => $result['maxNsu'] ?? $lastNsu,
                'last_sefaz_query_at' => now(),
            ]);

            Log::info('Busca SEFAZ concluída. NSU final: '.($result['maxNsu'] ?? $lastNsu).', Documentos processados: '.($result['count'] ?? 0));

            return $result;

        } catch (\Exception $e) {
            Log::error('Erro na busca SEFAZ: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca NF-es por NSU (do mais antigo ao mais novo).
     * Substitui a busca por período que não é suportada pela SEFAZ.
     * 
     * @param Empresa $empresa
     * @param int $nsuInicial NSU inicial (padrão: 0 para buscar desde o início)
     * @param int $maxConsultas Máximo de consultas para evitar bloqueios (padrão: 20)
     */
    public function buscarPorNsu(Empresa $empresa, int $nsuInicial = 0, int $maxConsultas = 20)
    {
        if (! $empresa->certificado_a1_path || ! $empresa->certificado_senha) {
            throw new \Exception('Certificado Digital não configurado para esta empresa.');
        }

        Log::info("Iniciando busca por NSU para {$empresa->nome} a partir do NSU: {$nsuInicial}");

        try {
            $certificate = $this->getCertificate($empresa);
            $config = $this->getConfig($empresa);
            $configJson = json_encode($config);

            $tools = new Tools($configJson, $certificate);
            $tools->model('55');

            // Nota: manifestarPendentes é executado apenas no buscarNovasNotas,
            // não aqui para evitar consumo indevido da cota da SEFAZ
            $ultNSU = $nsuInicial;
            $totalProcessados = 0;

            // SEFAZ limita a 1 requisção DistDFe por hora por CNPJ.
            // Fazemos apenas 1 lote por execução e salvamos o NSU para continuar na próxima.
            Log::info("Busca NSU: consultando a partir do NSU {$ultNSU}");

            $resp = $tools->sefazDistDFe($ultNSU);

            $result = $this->processResponse($resp, $empresa);
            $totalProcessados = $result['count'] ?? 0;

            $batchLastNsu = $result['lastNsu'] ?? $ultNSU;
            $globalMaxNsu = $result['maxNsu'] ?? $ultNSU;

            Log::info("NSU processados no lote: {$totalProcessados}, último NSU do lote: {$batchLastNsu}, Limite Global: {$globalMaxNsu}");

            // Salva o NSU do lote atual para continuar na próxima execução agendada
            $empresa->update([
                'last_nsu' => $batchLastNsu,
                'last_sefaz_query_at' => now(),
            ]);

            $hasMore = ($batchLastNsu < $globalMaxNsu);

            Log::info("Busca NSU concluída. Processados: {$totalProcessados}, NSU salvo: {$batchLastNsu}" . ($hasMore ? ", há mais documentos (máx: {$globalMaxNsu})" : ", fila esgotada."));

            return [
                'success' => true,
                'count' => $totalProcessados,
                'lastNsu' => $batchLastNsu,
                'maxNsu' => $globalMaxNsu,
                'hasMore' => $hasMore,
            ];

        } catch (\Exception $e) {
            Log::error('Erro na busca por NSU SEFAZ: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Consulta DistDFe por período usando a biblioteca nfephp
     * Versão com reflection completo para configurar todas as propriedades necessárias
     */
    private function consultaDistDFePorPeriodo(Tools $tools, Empresa $empresa, \Carbon\Carbon $dataIni, \Carbon\Carbon $dataFin, array $config)
    {
        $cUF = $this->getUfCode($config['siglaUF'] ?? 'SP');
        $cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);

        $dataIniStr = $dataIni->format('Y-m-d');
        $dataFinStr = $dataFin->format('Y-m-d');

        // Estrutura correta baseada na documentação da SEFAZ
        $consulta = '<distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">'
            .'<tpAmb>1</tpAmb>'
            ."<cUFAutor>{$cUF}</cUFAutor>"
            ."<CNPJ>{$cnpj}</CNPJ>"
            .'<distDFe>'
            ."<dtIni>{$dataIniStr}</dtIni>"
            ."<dtFin>{$dataFinStr}</dtFin>"
            .'</distDFe>'
            .'</distDFeInt>';

        Log::info("DEBUG - XML Consulta Período: {$consulta}");

        try {
            $reflection = new \ReflectionClass($tools);
            
            // Configura todas as propriedades necessárias para o sendRequest funcionar
            $props = [
                'urlService' => 'https://www1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
                'urlMethod' => 'nfeDistDFeInteresse',
                'urlAction' => '"http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe/nfeDistDFeInteresse"',
                'urlNamespace' => 'http://www.portalfiscal.inf.br/nfe',
                'lastRequest' => $consulta,
            ];
            
            foreach ($props as $propName => $propValue) {
                try {
                    $prop = $reflection->getProperty($propName);
                    $prop->setAccessible(true);
                    $prop->setValue($tools, $propValue);
                } catch (\Exception $e) {
                    Log::warning("DEBUG - Propriedade não encontrada: {$propName}");
                }
            }
            
            // Chama o método sendRequest
            $sendRequestMethod = $reflection->getMethod('sendRequest');
            $sendRequestMethod->setAccessible(true);
            
            $request = "<nfeDadosMsg xmlns=\"http://www.portalfiscal.inf.br/nfe\">{$consulta}</nfeDadosMsg>";
            $parameters = ['nfeDistDFeInteresse' => $request];
            $body = '<nfeDistDFeInteresse xmlns="http://www.portalfiscal.inf.br/nfe">'.$request.'</nfeDistDFeInteresse>';
            
            Log::info("DEBUG - Tentando enviar requisição SOAP para período...");
            
            return $sendRequestMethod->invoke($tools, $body, $parameters);
            
        } catch (\Exception $e) {
            Log::error("Erro na consulta por período: " . $e->getMessage());
            throw $e;
        }
    }

    private function getUfCode(string $uf): int
    {
        $codes = [
            'AC' => 12, 'AL' => 27, 'AP' => 16, 'BA' => 29, 'CE' => 23,
            'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21, 'MT' => 51,
            'MS' => 50, 'MG' => 31, 'PA' => 15, 'PB' => 25, 'PR' => 41,
            'PE' => 26, 'PI' => 22, 'RJ' => 33, 'RN' => 24, 'RS' => 43,
            'RO' => 11, 'RR' => 14, 'SC' => 42, 'SP' => 35, 'SE' => 28,
            'TO' => 17,
        ];

        return $codes[strtoupper($uf)] ?? 35;
    }

    private function getCertificate(Empresa $empresa)
    {
        $pfxContent = Storage::get($empresa->certificado_a1_path);
        if (! $pfxContent) {
            throw new \Exception('Arquivo do certificado não encontrado no storage.');
        }

        try {
            $cert = Certificate::readPfx($pfxContent, $empresa->certificado_senha);
            Log::info("Certificado lido com sucesso para empresa {$empresa->id}");

            return $cert;
        } catch (\Exception $e) {
            Log::error('Erro ao ler certificado: '.$e->getMessage());
            if (strpos($e->getMessage(), 'unsupported') !== false || strpos($e->getMessage(), '0308010C') !== false) {
                Log::warning("Certificado da empresa {$empresa->id} usa criptografia legada. Tentando converter...");

                return $this->convertLegacyCertificate($empresa, $pfxContent);
            }
            throw $e;
        }
    }

    private function convertLegacyCertificate(Empresa $empresa, $pfxContent)
    {
        $tempIn = tempnam(sys_get_temp_dir(), 'pfx_in_');
        $tempPem = tempnam(sys_get_temp_dir(), 'pem_');
        $tempOut = tempnam(sys_get_temp_dir(), 'pfx_out_');

        try {
            file_put_contents($tempIn, $pfxContent);

            $cmdExport = sprintf(
                'openssl pkcs12 -in %s -nodes -legacy -passin pass:%s -out %s',
                escapeshellarg($tempIn),
                escapeshellarg($empresa->certificado_senha),
                escapeshellarg($tempPem)
            );
            exec($cmdExport, $output, $returnVar);

            if ($returnVar !== 0 || ! file_exists($tempPem) || filesize($tempPem) === 0) {
                throw new \Exception('Falha ao converter certificado legado (exportação PEM).');
            }

            $cmdImport = sprintf(
                'openssl pkcs12 -export -in %s -out %s -passout pass:%s',
                escapeshellarg($tempPem),
                escapeshellarg($tempOut),
                escapeshellarg($empresa->certificado_senha)
            );
            exec($cmdImport, $output2, $returnVar2);

            if ($returnVar2 !== 0 || ! file_exists($tempOut) || filesize($tempOut) === 0) {
                throw new \Exception('Falha ao recriar certificado (importação PFX).');
            }

            $newPfxContent = file_get_contents($tempOut);
            $certificate = Certificate::readPfx($newPfxContent, $empresa->certificado_senha);

            Storage::put($empresa->certificado_a1_path, $newPfxContent);
            Log::info("Certificado da empresa {$empresa->id} convertido e salvo com sucesso.");

            return $certificate;

        } finally {
            @unlink($tempIn);
            @unlink($tempPem);
            @unlink($tempOut);
        }
    }

    private function getConfig(Empresa $empresa)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj);
        $uf = $empresa->cidade?->estado?->uf ?? 'SP';

        return [
            'atualizacao' => date('Y-m-d H:i:s'),
            'tpAmb' => (int) ($empresa->tpAmb ?? 1),
            'razaosocial' => $empresa->razao_social,
            'siglaUF' => $uf,
            'cnpj' => $cnpj,
            'schemes' => 'PL_009_V4',
            'versao' => '4.00',
            'proxy' => '',
            'urlNFe' => 'https://www1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
            'urlPortal' => 'https://nfe.fazenda.gov.br',
            'urlDistDFe' => 'https://www1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx',
        ];
    }

    private function processResponse($resp, Empresa $empresa)
    {
        $st = new Standardize;
        $xml = $st->simpleXml($resp);

        Log::info("DEBUG SEFAZ - Status: {$xml->cStat}, Motivo: {$xml->xMotivo}");
        Log::info("DEBUG SEFAZ - ultNSU: {$xml->ultNSU}, maxNSU: {$xml->maxNSU}");

        if ((string) $xml->cStat != '138') {
            Log::info("Retorno SEFAZ: {$xml->cStat} - {$xml->xMotivo}");
            if ((string) $xml->cStat == '137') {
                return ['count' => 0, 'maxNsu' => $empresa->last_nsu ?? 0];
            }
            throw new \Exception("Erro SEFAZ: {$xml->cStat} - {$xml->xMotivo}");
        }

        $globalMaxNsu = (int) $xml->maxNSU;
        $batchMaxNsu = $empresa->last_nsu ?? 0;
        $count = 0;

        // Debug: verificar se há documentos no retorno
        $docCount = isset($xml->loteDistDFeInt->docZip) ? count($xml->loteDistDFeInt->docZip) : 0;
        Log::info("DEBUG SEFAZ - Quantidade de documentos no retorno: {$docCount}");

        if (isset($xml->loteDistDFeInt->docZip)) {
            foreach ($xml->loteDistDFeInt->docZip as $doc) {
                $nsu = (int) $doc['NSU'];
                $batchMaxNsu = max($batchMaxNsu, $nsu);

                $schema = (string) $doc['schema'];
                $content = (string) $doc;
                Log::info("DEBUG SEFAZ - NSU: {$nsu}, Schema: {$schema}");

                $xmlContent = gzdecode(base64_decode($content));
                if ($xmlContent === false) {
                    Log::warning("DEBUG SEFAZ - Falha ao descompactar XML para NSU: {$nsu}");
                    continue;
                }

                if (strpos($schema, 'resNFe') !== false) {
                    $this->processarResumo($xmlContent, $empresa);
                    $count++;
                } elseif (strpos($schema, 'procNFe') !== false) {
                    $this->processarCompleta($xmlContent, $empresa);
                    $count++;
                } elseif (strpos($schema, 'resEvento') !== false) {
                    $this->processarEventoResumo($xmlContent, $empresa);
                    $count++;
                } elseif (strpos($schema, 'procEventoNFe') !== false) {
                    $this->processarEventoCompleto($xmlContent, $empresa);
                    $count++;
                }
            }
        }

        return [
            'count' => $count, 
            'lastNsu' => $batchMaxNsu, 
            'maxNsu' => $globalMaxNsu
        ];
    }

    private function processarResumo($xmlContent, Empresa $empresa)
    {
        $xml = simplexml_load_string($xmlContent);
        if (! $xml) {
            return;
        }

        $chNFe = (string) $xml->chNFe;
        $cnpjEmit = (string) $xml->CNPJ;
        $xNome = (string) $xml->xNome;
        $dEmi = (string) $xml->dhEmi ?: (string) $xml->dEmi;
        $vNF = (float) $xml->vNF;
        $tpNF = isset($xml->tpNF) ? (int) $xml->tpNF : null;

        // Determina se é nota de entrada ou saída
        $tipoNfe = $this->verificarTipoNfe($chNFe, $empresa, $tpNF, $cnpjEmit);

        if ($tipoNfe === 'emitida') {
            NfeEmitida::updateOrCreate(
                ['chave' => $chNFe, 'empresa_id' => $empresa->id],
                [
                    'emitente_cnpj' => $cnpjEmit,
                    'emitente_nome' => $xNome,
                    'data_emissao' => \Carbon\Carbon::parse($dEmi),
                    'valor_total' => $vNF,
                    'status' => 'autorizada',
                    'serie' => $this->extrairSerieDaChave($chNFe),
                ]
            );
        } else {
            NfeRecebida::updateOrCreate(
                ['chave' => $chNFe, 'empresa_id' => $empresa->id],
                [
                    'emitente_cnpj' => $cnpjEmit,
                    'emitente_nome' => $xNome,
                    'data_emissao' => \Carbon\Carbon::parse($dEmi),
                    'valor_total' => $vNF,
                    'status_manifestacao' => 'sem_manifesto',
                ]
            );
        }
    }

    private function processarCompleta($xmlContent, Empresa $empresa)
    {
        try {
            $xml = simplexml_load_string($xmlContent);
            if (! $xml) {
                return;
            }

            $infNFe = $xml->NFe->infNFe ?? $xml->infNFe;
            if (!$infNFe) {
                return;
            }
            
            $chNFe = preg_replace('/[^0-9]/', '', (string)$infNFe->attributes()->Id);
            $nomeArquivo = "sefaz_completa_{$chNFe}.xml";

            $fiscalService = app(\App\Services\FiscalService::class);
            $fiscalService->importXml($xmlContent, $empresa->id, $nomeArquivo);
            Log::info("DEBUG SEFAZ - NF-e Completa {$chNFe} importada com sucesso via FiscalService.");
        } catch (\Exception $e) {
            Log::error("DEBUG SEFAZ - Erro ao importar NF-e Completa via FiscalService: " . $e->getMessage());
        }
    }

    /**
     * Processa resumo de evento (manifestação, cancelamento, etc)
     */
    private function processarEventoResumo($xmlContent, Empresa $empresa)
    {
        $xml = simplexml_load_string($xmlContent);
        if (! $xml) {
            return;
        }

        $chNFe = (string) $xml->chNFe;
        $tpEvento = (string) $xml->tpEvento;
        $dhEvento = (string) $xml->dhEvento;
        $nSeqEvento = (int) $xml->nSeqEvento;

        // Mapeia códigos de eventos
        $eventos = [
            '210200' => 'Ciência da Operação',
            '210210' => 'Confirmação da Operação',
            '210220' => 'Operação não Realizada',
            '210240' => 'Desconhecimento da Operação',
            '110111' => 'Cancelamento',
            '110110' => 'Carta de Correção',
        ];

        $descricao = $eventos[$tpEvento] ?? "Evento {$tpEvento}";

        Log::info("Processando evento: {$descricao} para NFe {$chNFe}");

        // Atualiza o status de manifestação na NFe
        $nfe = NfeRecebida::where('chave', $chNFe)->where('empresa_id', $empresa->id)->first();
        
        if ($nfe) {
            $status = 'sem_manifesto';
            if ($tpEvento === '210200') $status = 'ciencia';
            if ($tpEvento === '210210') $status = 'confirmada';
            if ($tpEvento === '210220') $status = 'nao_realizada';
            if ($tpEvento === '210240') $status = 'desconhecida';
            if ($tpEvento === '110111') $status = 'cancelada';

            $nfe->update([
                'status_manifestacao' => $status,
                'data_manifestacao' => \Carbon\Carbon::parse($dhEvento),
            ]);
        }
    }

    /**
     * Processa evento completo (com XML)
     */
    private function processarEventoCompleto($xmlContent, Empresa $empresa)
    {
        try {
            $xml = simplexml_load_string($xmlContent);
            if (! $xml) {
                return;
            }

            // Extrai a chave da NFe do evento (pode estar em variados caminhos dependendo do schema)
            $chNFe = (string) ($xml->evento->infEvent->chNFe ?? 
                               $xml->procEvento->evento->infEvent->chNFe ?? 
                               $xml->infEvent->chNFe ?? 
                               '');

            if (empty($chNFe)) {
                // Tenta via xpath em caso de caminhos não mapeados
                $nodes = $xml->xpath('//chNFe');
                if (!empty($nodes)) {
                    $chNFe = (string) $nodes[0];
                }
            }

            if (empty($chNFe)) {
                Log::warning("DEBUG SEFAZ - Evento sem chave NFe encontrada para XML recebido.");
                return;
            }

            $tpEvento = isset($xml->evento->infEvent->tpEvento) ? (string) $xml->evento->infEvent->tpEvento : '';
            $dhEvento = isset($xml->evento->infEvent->dhEvento) ? (string) $xml->evento->infEvent->dhEvento : now();
            
            $nomeArquivo = "sefaz_evento_{$chNFe}_{$tpEvento}.xml";

            $fiscalService = app(\App\Services\FiscalService::class);
            $fiscalService->importXml($xmlContent, $empresa->id, $nomeArquivo);
            Log::info("DEBUG SEFAZ - Evento Completo {$chNFe} importado com sucesso via FiscalService.");

            // Atualiza o status de manifestação na NFe
            $nfe = NfeRecebida::where('chave', $chNFe)->where('empresa_id', $empresa->id)->first();
            
            if ($nfe) {
                $status = $nfe->status_manifestacao;
                if ($tpEvento === '210200') $status = 'ciencia';
                if ($tpEvento === '210210') $status = 'confirmada';
                if ($tpEvento === '210220') $status = 'nao_realizada';
                if ($tpEvento === '210240') $status = 'desconhecida';
                if ($tpEvento === '110111') $status = 'cancelada';

                $nfe->update([
                    'status_manifestacao' => $status,
                    'data_manifestacao' => \Carbon\Carbon::parse($dhEvento),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("DEBUG SEFAZ - Erro ao importar Evento Completo via FiscalService: " . $e->getMessage());
        }
    }

    /**
     * Realiza a manifestação de uma única NF-e.
     */
    public function manifestar(Empresa $empresa, string $chNFe, string $tpEvento, string $xJust = '', ?Tools $tools = null)
    {
        if (! $empresa->certificado_a1_path || ! $empresa->certificado_senha) {
            throw new \Exception('Certificado Digital não configurado para esta empresa.');
        }

        try {
            if (!$tools) {
                $certificate = $this->getCertificate($empresa);
                $config = $this->getConfig($empresa);
                $tools = new Tools(json_encode($config), $certificate);
                $tools->model('55');
            }

            $resp = $tools->sefazManifesta($chNFe, $tpEvento, $xJust, 1);

            $st = new Standardize();
            $xmlResp = $st->simpleXml($resp);

            $cStat = (string) $xmlResp->retEvento->infEvento->cStat;
            $xMotivo = (string) $xmlResp->retEvento->infEvento->xMotivo;

            // 135 = Evento registrado e vinculado
            // 136 = Evento registrado e vinculado a NF-e cancelada
            // 573 = Duplicidade de evento (já manifestada)
            if (in_array($cStat, ['135', '136', '573'])) {
                 Log::info("Manifestação {$tpEvento} sucesso para {$chNFe} - {$xMotivo}");
                 return true;
            }

            Log::warning("Erro na manifestação da NF-e {$chNFe}: {$cStat} - {$xMotivo}");
            return false;

        } catch (\Exception $e) {
            Log::error("Erro ao manifestar NF-e {$chNFe}: ".$e->getMessage());
            return false;
        }
    }

    /**
     * Manifesta notas pendentes se a empresa tiver auto_ciencia ativo.
     * Limita a 20 notas para evitar timeout e bloqueios
     */
    public function manifestarPendentes(Empresa $empresa, ?Tools $tools = null)
    {
        if (! $empresa->auto_ciencia) {
            return;
        }

        $pendentes = NfeRecebida::where('empresa_id', $empresa->id)
            ->where('status_manifestacao', 'sem_manifesto')
            ->limit(20)
            ->get();

        if ($pendentes->count() === 0) {
            return;
        }

        Log::info("Iniciando auto-manifestação de {$pendentes->count()} notas para empresa {$empresa->id}");

        foreach ($pendentes as $nfe) {
            try {
                // 210200 = Ciência da Operação
                $sucesso = $this->manifestar($empresa, $nfe->chave, '210200', '', $tools);
                
                if ($sucesso) {
                    $nfe->update([
                        'status_manifestacao' => 'ciencia',
                        'data_manifestacao' => now(),
                    ]);
                }
                
                // Pausa curta entre eventos para evitar erro de consumo indevido da SEFAZ
                sleep(2);
            } catch (\Exception $e) {
                Log::error("Exceção ao manifestar NFe {$nfe->chave}: " . $e->getMessage());
            }
        }
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
