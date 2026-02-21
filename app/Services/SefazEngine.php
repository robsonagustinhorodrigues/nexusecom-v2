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
    private function verificarTipoNfe(string $chave, Empresa $empresa): string
    {
        // Extrai CNPJ emitente da chave NFe
        $cnpjEmitente = substr(preg_replace('/[^0-9]/', '', $chave), 6, 14);
        $cnpjEmpresa = preg_replace('/[^0-9]/', '', $empresa->cnpj);
        
        // Remove zeros à esquerda para comparação
        $cnpjEmitente = ltrim($cnpjEmitente, '0');
        $cnpjEmpresa = ltrim($cnpjEmpresa, '0');
        
        if (!empty($cnpjEmitente) && !empty($cnpjEmpresa) && $cnpjEmitente === $cnpjEmpresa) {
            return 'emitida'; // É uma nota de SAÍDA
        }
        
        return 'recebida'; // É uma nota de ENTRADA
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

            $ultNSU = $nsuInicial;
            $maxNSU = $ultNSU;
            $totalProcessados = 0;
            $iCount = 0;

            // Loop de busca por NSU
            while ($ultNSU <= $maxNSU && $iCount < $maxConsultas) {
                $iCount++;
                
                Log::info("Busca NSU {$iCount}/{$maxConsultas}: consultando a partir do NSU {$ultNSU}");

                try {
                    // Usa o método nativo do nfephp
                    $resp = $tools->sefazDistDFe($ultNSU);
                    
                    // Processa o retorno
                    $result = $this->processResponse($resp, $empresa);
                    
                    $totalProcessados += $result['count'] ?? 0;
                    $ultNSU = ($result['maxNsu'] ?? $ultNSU) + 1;
                    $maxNSU = $result['maxNsu'] ?? $ultNSU;
                    
                    Log::info("NSU processados: {$result['count']}, próximo NSU: {$ultNSU}, max NSU: {$maxNSU}");
                    
                    // Verifica se chegou ao final
                    if ($ultNSU > $maxNSU) {
                        Log::info("Chegou ao final dos documentos disponíveis");
                        break;
                    }
                    
                    // Pausa entre consultas para evitar bloqueios
                    sleep(2);
                    
                } catch (\Exception $e) {
                    $erroMsg = $e->getMessage();
                    
                    // Verifica se é erro de consumo indevido (bloqueio)
                    if (strpos($erroMsg, '656') !== false || strpos($erroMsg, 'Consumo Indevido') !== false) {
                        Log::warning("Bloqueio da SEFAZ detectado. Interrompendo busca.");
                        throw new \Exception("Bloqueio da SEFAZ: Aguarde 1 hora antes de nova consulta.");
                    }
                    
                    Log::error("Erro na consulta NSU {$ultNSU}: " . $erroMsg);
                    break;
                }
            }

            // Atualiza o último NSU processado
            $empresa->update([
                'last_nsu' => $maxNSU,
                'last_sefaz_query_at' => now(),
            ]);

            Log::info("Busca por NSU concluída. Total processados: {$totalProcessados}, NSU final: {$maxNSU}");

            return [
                'success' => true, 
                'count' => $totalProcessados,
                'ultNSU' => $ultNSU,
                'maxNSU' => $maxNSU,
                'consultas' => $iCount
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

        $maxNsu = $empresa->last_nsu ?? 0;
        $count = 0;

        // Debug: verificar se há documentos no retorno
        $docCount = isset($xml->loteDistDFeInt->docZip) ? count($xml->loteDistDFeInt->docZip) : 0;
        Log::info("DEBUG SEFAZ - Quantidade de documentos no retorno: {$docCount}");

        if (isset($xml->loteDistDFeInt->docZip)) {
            foreach ($xml->loteDistDFeInt->docZip as $doc) {
                $nsu = (int) $doc['NSU'];
                $maxNsu = max($maxNsu, $nsu);

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

        return ['count' => $count, 'maxNsu' => $maxNsu];
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

        // Determina se é nota de entrada ou saída
        $tipoNfe = $this->verificarTipoNfe($chNFe, $empresa);

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
        $xml = simplexml_load_string($xmlContent);
        if (! $xml) {
            return;
        }

        $infNFe = $xml->NFe->infNFe;
        $chNFe = preg_replace('/[^0-9]/', '', (string) $infNFe->attributes()->Id);

        // Determina se é nota de entrada ou saída
        $tipoNfe = $this->verificarTipoNfe($chNFe, $empresa);

        $path = $tipoNfe === 'emitida' 
            ? 'nfes/emitidas/'.date('Y/m')."/{$chNFe}.xml"
            : 'nfes/recebidas/'.date('Y/m')."/{$chNFe}.xml";
        Storage::put($path, $xmlContent);

        if ($tipoNfe === 'emitida') {
            NfeEmitida::updateOrCreate(
                ['chave' => $chNFe, 'empresa_id' => $empresa->id],
                [
                    'numero' => (string) $infNFe->ide->nNF,
                    'serie' => (string) ($infNFe->ide->serie ?? 1),
                    'valor_total' => (float) $infNFe->total->ICMSTot->vNF,
                    'emitente_cnpj' => (string) ($infNFe->emit->CNPJ ?: $infNFe->emit->CPF),
                    'emitente_nome' => (string) $infNFe->emit->xNome,
                    'xml_path' => $path,
                    'status' => 'autorizada',
                ]
            );
        } else {
            NfeRecebida::updateOrCreate(
                ['chave' => $chNFe, 'empresa_id' => $empresa->id],
                [
                    'numero' => (string) $infNFe->ide->nNF,
                    'serie' => (string) $infNFe->ide->serie,
                    'valor_total' => (float) $infNFe->total->ICMSTot->vNF,
                    'emitente_cnpj' => (string) ($infNFe->emit->CNPJ ?: $infNFe->emit->CPF),
                    'emitente_nome' => (string) $infNFe->emit->xNome,
                    'xml_path' => $path,
                ]
            );
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
            if ($tpEvento === '210200') $status = 'ciencia_operacao';
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
        $xml = simplexml_load_string($xmlContent);
        if (! $xml) {
            return;
        }

        // Extrai a chave da NFe do evento
        $chNFe = '';
        if (isset($xml->evento->infEvent->chNFe)) {
            $chNFe = (string) $xml->evento->infEvent->chNFe;
        } elseif (isset($xml->procEvento->evento->infEvent->chNFe)) {
            $chNFe = (string) $xml->procEvento->evento->infEvent->chNFe;
        }

        if (empty($chNFe)) {
            Log::warning("Evento sem chave NFe encontrada");
            return;
        }

        $tpEvento = isset($xml->evento->infEvent->tpEvento) ? (string) $xml->evento->infEvent->tpEvento : '';
        $dhEvento = isset($xml->evento->infEvent->dhEvento) ? (string) $xml->evento->infEvent->dhEvento : now();

        // Salva o XML do evento
        $path = 'eventos/'.date('Y/m')."/{$chNFe}_{$tpEvento}.xml";
        Storage::put($path, $xmlContent);

        Log::info("Processando evento completo: {$tpEvento} para NFe {$chNFe}");

        // Atualiza o status de manifestação na NFe
        $nfe = NfeRecebida::where('chave', $chNFe)->where('empresa_id', $empresa->id)->first();
        
        if ($nfe) {
            $status = $nfe->status_manifestacao;
            if ($tpEvento === '210200') $status = 'ciencia_operacao';
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
