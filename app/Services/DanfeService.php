<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class DanfeService
{
    private array $config = [];
    private array $nfeData = [];

    public function gerarDanfeA4(string $xml, array $empresa): string
    {
        try {
            $danfe = new \NFePHP\DA\NFe\Danfe($xml);
            $danfe->exibirTextoFatura = false;
            $danfe->exibirPIS = false;
            $danfe->exibirCOFINS = false;
            $danfe->exibirIcmsST = false;
            
            if (!empty($empresa['logo_path'])) {
                $logoPath = storage_path('app/public/' . $empresa['logo_path']);
                if (file_exists($logoPath)) {
                    $danfe->logoParameters($logoPath, 'C', false);
                }
            }
            
            $danfe->setDefaultFont('times');
            $pdf = $danfe->render();
            
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="DANFE_A4.pdf"',
            ])->getContent();
        } catch (\Exception $e) {
            return $this->gerarDanfeSimplificado($xml, $empresa);
        }
    }

    public function gerarDanfeSimplificado(string $xml, array $empresa): string
    {
        $this->config = [
            'show_logo' => $empresa['danfe_show_logo'] ?? true,
            'logo_position' => $empresa['danfe_logo_position'] ?? 'top',
            'show_itens' => $empresa['danfe_show_itens'] ?? true,
            'show_valor_itens' => $empresa['danfe_show_valor_itens'] ?? true,
            'show_valor_total' => $empresa['danfe_show_valor_total'] ?? true,
            'show_qrcode' => $empresa['danfe_show_qrcode'] ?? true,
            'rodape' => $empresa['danfe_rodape'] ?? null,
            'logo_path' => $empresa['logo_path'] ?? null,
        ];

        $this->nfeData = $this->parseXml($xml);

        return $this->generateHtml();
    }

    public function gerarEtiqueta(string $xml, array $empresa): string
    {
        $this->config = [
            'show_logo' => $empresa['danfe_show_logo'] ?? true,
            'logo_path' => $empresa['logo_path'] ?? null,
        ];

        $this->nfeData = $this->parseXml($xml);

        return $this->generateEtiquetaHtml();
    }

    private function generateBarcode(string $content): string
    {
        try {
            $generator = new BarcodeGeneratorPNG();
            $barcode = base64_encode($generator->getBarcode($content, $generator::TYPE_CODE_128, 2, 50));
            return 'data:image/png;base64,' . $barcode;
        } catch (\Exception $e) {
            return '';
        }
    }

    private function generateHtmlA4(): string
    {
        $data = $this->nfeData;
        $cfg = $this->config;
        
        $logoUrl = null;
        if ($cfg['show_logo'] && $cfg['logo_path']) {
            $logoUrl = Storage::url($cfg['logo_path']);
        }

        $chave = $data['chave'] ?? '';
        $barcodeData = $this->generateBarcode($chave);
        
        $qtdItens = $data['quantidade_itens'] ?? 1;
        $qtdDestaqueClass = $qtdItens > 1 ? 'qtd-multiplos' : 'qtd-unico';
        $qtdDestaqueTexto = $qtdItens > 1 ? 'ITENS PARA SEPARAR' : 'ÚNICO ITEM';
        $qtdDestaqueNum = $qtdItens;
        $qtdDestaqueLabel = $qtdItens > 1 ? 'ITENS' : 'ITEM';
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DANFE A4 - {$data['numero']}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .danfe-a4 { width: 210mm; min-height: 297mm; padding: 5mm; margin: 0 auto; }
        
        .header { display: flex; border: 1px solid #333; margin-bottom: 5mm; }
        .header-logo { width: 25%; padding: 5mm; text-align: center; border-right: 1px solid #333; }
        .header-logo img { max-width: 80px; max-height: 60px; }
        .header-info { width: 50%; padding: 5mm; border-right: 1px solid #333; }
        .header-info h1 { font-size: 14px; margin-bottom: 3mm; }
        .header-info p { font-size: 9px; margin-bottom: 2mm; }
        .header-qr { width: 25%; padding: 5mm; text-align: center; }
        
        .doc-title { text-align: center; border: 2px solid #333; padding: 3mm; margin-bottom: 5mm; }
        .doc-title h2 { font-size: 12px; margin-bottom: 2mm; }
        
        .section { border: 1px solid #333; margin-bottom: 5mm; padding: 3mm; }
        .section h3 { font-size: 9px; background: #eee; padding: 2mm; margin: -3mm -3mm 3mm -3mm; border-bottom: 1px solid #333; }
        
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; font-weight: bold; background: #f9f9f9; padding: 2mm 3mm; width: 30%; border-bottom: 1px solid #ddd; }
        .info-value { display: table-cell; padding: 2mm 3mm; border-bottom: 1px solid #ddd; }
        
        .qtd-destaque { 
            text-align: center; 
            padding: 3mm; 
            margin: 3mm 0;
            border: 2px solid;
        }
        .qtd-unico { background: #e8f5e9; border-color: #4caf50; }
        .qtd-multiplos { background: #fff3e0; border-color: #ff9800; }
        .qtd-destaque .num { font-size: 24px; font-weight: bold; }
        .qtd-destaque .label { font-size: 9px; text-transform: uppercase; }
        
        table.itens { width: 100%; border-collapse: collapse; font-size: 8px; }
        table.itens th { background: #333; color: #fff; padding: 2mm; text-align: left; border: 1px solid #333; }
        table.itens td { padding: 2mm; border: 1px solid #333; }
        table.itens tr:nth-child(even) { background: #f9f9f9; }
        
        .totais { background: #f0f0f0; padding: 3mm; margin-top: 3mm; }
        .totais-row { display: flex; justify-content: space-between; padding: 1mm 0; }
        .totais-final { font-size: 14px; font-weight: bold; border-top: 2px solid #333; padding-top: 2mm; margin-top: 2mm; }
        
        .chave-box { background: #f5f5f5; padding: 3mm; text-align: center; border: 1px solid #333; margin: 3mm 0; }
        .chave-box .label { font-size: 8px; color: #666; }
        .chave-box .valor { font-size: 10px; font-family: monospace; font-weight: bold; }
        
        .footer { font-size: 8px; color: #666; text-align: center; margin-top: 5mm; border-top: 1px solid #ccc; padding-top: 3mm; }
        
        @media print {
            .danfe-a4 { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="danfe-a4">
HTML;

        if ($logoUrl) {
            $html .= <<<HTML
        <div class="header">
            <div class="header-logo">
                <img src="{$logoUrl}" alt="Logo">
            </div>
            <div class="header-info">
                <h1>{$data['emitente']['nome']}</h1>
                <p><strong>CNPJ:</strong> {$data['emitente']['cnpj']}</p>
                <p><strong>IE:</strong> {$data['emitente']['ie']}</p>
                <p>{$data['emitente']['endereco']}, {$data['emitente']['numero']} - {$data['emitente']['bairro']}</p>
                <p>{$data['emitente']['municipio']} - {$data['emitente']['uf']} - CEP: {$data['emitente']['cep']}</p>
            </div>
            <div class="header-qr">
                <div style="font-size: 9px; font-weight: bold;">DANFE</div>
                <div style="font-size: 11px; font-weight: bold; margin: 2mm 0;">Nº {$data['numero']}</div>
                <div style="font-size: 9px;">Série {$data['serie']}</div>
            </div>
        </div>
HTML;
        }

        $tipoOperacao = $data['tipo_operacao'] == '0' ? 'ENTRADA' : 'SAÍDA';
        $tipoDoc = $data['modelo'] == '65' ? 'NFC-e' : 'NF-e';

        $html .= <<<HTML
        <div class="doc-title">
            <h2>{$tipoDoc} - {$tipoOperacao}</h2>
            <div style="font-size: 9px;">Data de Emissão: {$data['data_emissao']}</div>
        </div>
        
        <div class="section">
            <h3>DESTINATÁRIO</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Nome/Razão Social:</div>
                    <div class="info-value">{$data['destinatario']['nome']}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">CNPJ/CPF:</div>
                    <div class="info-value">{$data['destinatario']['cnpj']}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h3>EMITENTE</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">CNPJ:</div>
                    <div class="info-value">{$data['emitente']['cnpj']}{$data['emitente']['cpf']}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Inscrição Estadual:</div>
                    <div class="info-value">{$data['emitente']['ie']}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Endereço:</div>
                    <div class="info-value">{$data['emitente']['endereco']}, {$data['emitente']['numero']} - {$data['emitente']['bairro']} - {$data['emitente']['municipio']}/{$data['emitente']['uf']}</div>
                </div>
            </div>
        </div>
        
        <div class="qtd-destaque {$qtdDestaqueClass}">
            <div class="num">{$qtdDestaqueNum}</div>
            <div class="label">{$qtdDestaqueLabel}</div>
        </div>
        
        <div class="section">
            <h3>ITENS DA NOTA FISCAL</h3>
            <table class="itens">
                <thead>
                    <tr>
                        <th style="width:12%">Código</th>
                        <th style="width:40%">Descrição</th>
                        <th style="width:10%" class="text-center">NCM</th>
                        <th style="width:8%" class="text-center">Qtd</th>
                        <th style="width:8%">Un</th>
                        <th style="width:11%" class="text-right">Vl. Unit</th>
                        <th style="width:11%" class="text-right">Vl. Total</th>
                    </tr>
                </thead>
                <tbody>
HTML;

        foreach ($data['itens'] ?? [] as $item) {
            $html .= <<<HTML
                    <tr>
                        <td>{$item['codigo']}</td>
                        <td>{$item['descricao']}</td>
                        <td class="text-center">{$item['ncm']}</td>
                        <td class="text-center">{$item['quantidade']}</td>
                        <td>{$item['unidade']}</td>
                        <td class="text-right">R$ {$item['valor_unitario']}</td>
                        <td class="text-right">R$ {$item['valor_total']}</td>
                    </tr>
HTML;
        }

        $html .= <<<HTML
                </tbody>
            </table>
        </div>
        
        <div class="totais">
            <div class="totais-row">
                <span>Valor dos Produtos:</span>
                <span>R$ {$data['totais']['produtos']}</span>
            </div>
HTML;

        if ($data['totais']['frete'] > 0) {
            $html .= <<<HTML
            <div class="totais-row">
                <span>Frete:</span>
                <span>R$ {$data['totais']['frete']}</span>
            </div>
HTML;
        }

        if ($data['totais']['desconto'] > 0) {
            $html .= <<<HTML
            <div class="totais-row">
                <span>Desconto:</span>
                <span>R$ {$data['totais']['desconto']}</span>
            </div>
HTML;
        }

        if ($data['totais']['icms'] > 0) {
            $html .= <<<HTML
            <div class="totais-row">
                <span>ICMS:</span>
                <span>R$ {$data['totais']['icms']}</span>
            </div>
HTML;
        }

        $html .= <<<HTML
            <div class="totais-row totais-final">
                <span>TOTAL DA NOTA:</span>
                <span>R$ {$data['totais']['total']}</span>
            </div>
        </div>
        
        <div class="chave-box">
            <div class="label">CHAVE DE ACESSO</div>
            <div class="valor">{$chave}</div>
            <div style="margin-top:2mm;">
                <img src="{$barcodeData}" alt="Barcode" style="height: 40px;">
            </div>
        </div>
        
        <div class="footer">
            <p>Documento emitido via sistema NexusEcom</p>
            <p>NF-e {$tipoDoc} - {$data['numero']}/{$data['serie']} - {$data['data_emissao']}</p>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
HTML;

        return $html;
    }

    private function generateEtiquetaHtml(): string
    {
        $data = $this->nfeData;
        $cfg = $this->config;
        
        $logoUrl = null;
        if ($cfg['show_logo'] && $cfg['logo_path']) {
            $logoUrl = Storage::url($cfg['logo_path']);
        }

        $chave = $data['chave'] ?? '';
        $barcodeData = $this->generateBarcode($chave);
        
        $qtdItens = $data['quantidade_itens'] ?? 1;
        $qtdDestaqueClass = $qtdItens > 1 ? 'qtd-multiplos' : 'qtd-unico';
        $qtdDestaqueTexto = $qtdItens > 1 ? 'ITENS PARA SEPARAR' : 'ÚNICO ITEM';
        $qtdDestaqueNum = $qtdItens;
        $qtdDestaqueLabel = $qtdItens > 1 ? 'ITENS' : 'ITEM';
        
        $itens = array_slice($data['itens'] ?? [], 0, 5);
        $temMaisItens = count($data['itens'] ?? []) > 5;
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Etiqueta NF-e</title>
    <style>
        @page { size: 100mm 150mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; }
        .etiqueta { 
            width: 100mm; 
            height: 150mm; 
            padding: 3mm; 
            font-size: 9px;
            overflow: hidden;
        }
        .header { display: flex; align-items: center; margin-bottom: 2mm; }
        .logo { width: 15mm; height: 15mm; object-fit: contain; margin-right: 2mm; }
        .info-empresa { flex: 1; overflow: hidden; }
        .info-empresa .nome { font-size: 8px; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .info-empresa .doc { font-size: 7px; color: #666; }
        
        .chave-row { display: flex; gap: 2mm; margin-bottom: 2mm; }
        .chave-box { flex: 1; background: #f5f5f5; padding: 1mm; font-size: 6px; }
        .chave-box .label { font-size: 5px; color: #666; text-transform: uppercase; }
        .chave-box .valor { font-size: 6px; font-family: monospace; font-weight: bold; word-break: break-all; line-height: 1; }
        
        .barcode { text-align: center; margin-bottom: 2mm; }
        .barcode img { width: 90mm; height: 12mm; }
        
        .dados-row { display: flex; gap: 2mm; margin-bottom: 2mm; font-size: 7px; }
        .dados-box { flex: 1; background: #f9f9f9; padding: 1mm; }
        .dados-box .label { font-size: 5px; color: #888; }
        .dados-box .valor { font-weight: bold; }
        
        .qtd-destaque { 
            text-align: center; 
            padding: 2mm; 
            margin-bottom: 2mm;
            border: 2px solid;
        }
        .qtd-unico { background: #e8f5e9; border-color: #4caf50; }
        .qtd-multiplos { background: #fff3e0; border-color: #ff9800; }
        .qtd-destaque .num { font-size: 20px; font-weight: bold; }
        .qtd-destaque .label { font-size: 7px; text-transform: uppercase; }
        
        .itens-table { width: 100%; font-size: 7px; border-collapse: collapse; }
        .itens-table th { background: #333; color: #fff; padding: 1mm; text-align: left; font-size: 6px; }
        .itens-table td { padding: 1mm; border-bottom: 1px solid #ddd; }
        .itens-table tr:nth-child(even) { background: #f9f9f9; }
        .itens-table .cod { width: 20%; font-size: 6px; }
        .itens-table .desc { width: 50%; }
        .itens-table .qtd { width: 10%; text-align: center; }
        .itens-table .unid { width: 8%; text-align: center; }
        .itens-table .total { width: 12%; text-align: right; }
        
        .footer { font-size: 5px; color: #999; text-align: center; margin-top: 1mm; }
        
        @media print {
            .etiqueta { page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="etiqueta">
HTML;

        if ($logoUrl) {
            $html .= <<<HTML
        <div class="header">
            <img src="{$logoUrl}" class="logo" alt="Logo">
            <div class="info-empresa">
                <div class="nome">{$data['emitente']['nome']}</div>
                <div class="doc">CNPJ: {$data['emitente']['cnpj']}</div>
            </div>
        </div>
HTML;
        }

        $html .= <<<HTML
        <div class="chave-row">
            <div class="chave-box">
                <div class="label">Chave de Acesso</div>
                <div class="valor">{$chave}</div>
            </div>
        </div>
        
        <div class="barcode">
            <img src="{$barcodeData}" alt="Barcode">
        </div>
        
        <div class="dados-row">
            <div class="dados-box">
                <div class="label">NF-e</div>
                <div class="valor">{$data['numero']} / {$data['serie']}</div>
            </div>
            <div class="dados-box">
                <div class="label">Destinatário</div>
                <div class="valor">{$data['destinatario']['nome']}</div>
            </div>
        </div>
        
        <div class="qtd-destaque {$qtdDestaqueClass}">
            <div class="num">{$qtdDestaqueNum}</div>
            <div class="label">{$qtdDestaqueLabel}</div>
        </div>
        
        <table class="itens-table">
            <thead>
                <tr>
                    <th class="cod">Código</th>
                    <th class="desc">Descrição</th>
                    <th class="qtd">Qtd</th>
                    <th class="unid">Un</th>
                    <th class="total">Total</th>
                </tr>
            </thead>
            <tbody>
HTML;

        foreach ($itens as $item) {
            $html .= <<<HTML
                <tr>
                    <td class="cod">{$item['codigo']}</td>
                    <td class="desc">{$item['descricao']}</td>
                    <td class="qtd">{$item['quantidade']}</td>
                    <td class="unid">{$item['unidade']}</td>
                    <td class="total">R$ {$item['valor_total']}</td>
                </tr>
HTML;
        }

        if ($temMaisItens) {
            $itensExtras = count($data['itens'] ?? []) - 5;
            $html .= <<<HTML
                <tr style="background: #ffebee;">
                    <td colspan="5" style="text-align: center; color: #c62828; font-weight: bold;">
                        + {$itensExtras} ITENS - CONSULTE O DANFE COMPLETO
                    </td>
                </tr>
HTML;
        }

        $html .= <<<HTML
            </tbody>
        </table>
        
        <div class="footer">
            NF-e Modelo {$data['modelo']} | {$data['data_emissao']} | Valor: R$ {$data['totais']['total']}
        </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
HTML;

        return $html;
    }

    private function parseXml(string $xml): array
    {
        try {
            $xmlObj = simplexml_load_string($xml);
            $ns = $xmlObj->getNamespaces(true);
            
            $data = [];
            
            $infNFe = $xmlObj->NFe->infNFe;
            $data['chave'] = (string) $infNFe['Id'];
            $data['chave'] = str_replace('NFe', '', $data['chave']);
            
            $data['numero'] = (string) $infNFe->ide->nNF;
            $data['serie'] = (string) $infNFe->ide->serie;
            $data['data_emissao'] = (string) $infNFe->ide->dhEmi;
            $data['modelo'] = (string) $infNFe->ide->mod;
            $data['tipo_operacao'] = (string) $infNFe->ide->tpNF;
            
            $emit = $infNFe->emit;
            $data['emitente'] = [
                'nome' => (string) $emit->xNome,
                'fantasia' => (string) $emit->xFant,
                'cnpj' => (string) $emit->CNPJ,
                'cpf' => (string) $emit->CPF ?? null,
                'ie' => (string) $emit->IE,
                'endereco' => (string) $emit->enderEmit->xLgr,
                'numero' => (string) $emit->enderEmit->nro,
                'bairro' => (string) $emit->enderEmit->xBairro,
                'municipio' => (string) $emit->enderEmit->xMun,
                'uf' => (string) $emit->enderEmit->UF,
                'cep' => (string) $emit->enderEmit->CEP,
            ];

            $dest = $infNFe->dest;
            $data['destinatario'] = [
                'nome' => (string) ($dest->xNome ?? 'CONSUMIDOR'),
                'cnpj' => (string) ($dest->CNPJ ?? $dest->CPF ?? ''),
            ];

            $data['itens'] = [];
            if (isset($infNFe->det)) {
                foreach ($infNFe->det as $item) {
                    $prod = $item->prod;
                    $imposto = $item->imposto;
                    
                    $data['itens'][] = [
                        'codigo' => (string) $prod->cProd,
                        'descricao' => (string) $prod->xProd,
                        'ncm' => (string) $prod->NCM,
                        'unidade' => (string) $prod->uCom,
                        'quantidade' => (float) $prod->qCom,
                        'valor_unitario' => (float) $prod->vUnCom,
                        'valor_total' => (float) $prod->vProd,
                        'codigo_barras' => (string) $prod->cEAN,
                    ];
                }
            }

            $total = $infNFe->total->ICMSTot;
            $data['totais'] = [
                'base_calculo' => (float) ($total->vBC ?? 0),
                'icms' => (float) ($total->vICMS ?? 0),
                'icms_st' => (float) ($total->vICMSST ?? 0),
                'produtos' => (float) ($total->vProd ?? 0),
                'frete' => (float) ($total->vFrete ?? 0),
                'seguro' => (float) ($total->vSeg ?? 0),
                'desconto' => (float) ($total->vDesc ?? 0),
                'outros' => (float) ($total->vOutro ?? 0),
                'total' => (float) ($total->vNF ?? 0),
            ];

            $data['quantidade_itens'] = count($data['itens']);

            if (isset($infNFe->infAdic->infCpl)) {
                $data['informacoes_complementares'] = (string) $infNFe->infAdic->infCpl;
            }

            return $data;
        } catch (\Exception $e) {
            return [
                'error' => 'Erro ao processar XML: ' . $e->getMessage(),
                'chave' => '',
                'numero' => '0',
                'serie' => '0',
            ];
        }
    }

    private function generateHtml(): string
    {
        $data = $this->nfeData;
        $cfg = $this->config;

        $logoUrl = null;
        if ($cfg['show_logo'] && $cfg['logo_path']) {
            $logoUrl = Storage::url($cfg['logo_path']);
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DANFE - {$data['numero']}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .danfe { max-width: 80mm; margin: 0 auto; border: 1px solid #ccc; padding: 5px; }
        .header { text-align: center; border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 10px; }
        .logo { max-width: 50mm; max-height: 25mm; margin-bottom: 5px; }
        .company-name { font-size: 14px; font-weight: bold; }
        .title { font-size: 16px; font-weight: bold; margin: 5px 0; }
        .subtitle { font-size: 10px; }
        .info-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; font-weight: bold; padding: 2px 5px; background: #f0f0f0; width: 35%; }
        .info-value { display: table-cell; padding: 2px 5px; }
        .section-title { background: #333; color: #fff; padding: 3px 5px; font-weight: bold; margin: 10px 0 5px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th { background: #f0f0f0; padding: 3px; text-align: left; border: 1px solid #ccc; }
        td { padding: 3px; border: 1px solid #ccc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .total-section { background: #f0f0f0; padding: 5px; margin-top: 10px; }
        .total-row { display: flex; justify-content: space-between; padding: 2px 0; }
        .total-final { font-size: 14px; font-weight: bold; border-top: 2px solid #333; padding-top: 5px; margin-top: 5px; }
        .footer { margin-top: 15px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 9px; text-align: center; }
        .chave-box { background: #f5f5f5; padding: 5px; word-break: break-all; font-size: 9px; font-family: monospace; }
        .quantidade-destaque { background: #e3f2fd; padding: 10px; text-align: center; margin-bottom: 10px; border: 2px solid #2196f3; }
        .quantidade-destaque .num { font-size: 24px; font-weight: bold; color: #2196f3; }
        .quantidade-destaque .label { font-size: 10px; color: #666; }
        .quantidade-unico { background: #e8f5e9; padding: 15px; text-align: center; margin-bottom: 10px; border: 3px solid #4caf50; border-radius: 10px; }
        .quantidade-unico .num { font-size: 36px; font-weight: bold; color: #2e7d32; }
        .quantidade-unico .label { font-size: 12px; color: #388e3c; font-weight: bold; text-transform: uppercase; }
        .quantidade-multiplos { background: #fff3e0; padding: 15px; text-align: center; margin-bottom: 10px; border: 3px solid #ff9800; border-radius: 10px; }
        .quantidade-multiplos .num { font-size: 48px; font-weight: bold; color: #e65100; }
        .quantidade-multiplos .label { font-size: 12px; color: #ef6c00; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="danfe">
HTML;

        if ($cfg['show_logo'] && $logoUrl) {
            $html .= <<<HTML
        <div class="header">
            <img src="{$logoUrl}" class="logo" alt="Logo">
            <div class="company-name">{$data['emitente']['nome']}</div>
            <div class="subtitle">{$data['emitente']['fantasia']}</div>
        </div>
HTML;
        }

        $tipoOperacao = $data['tipo_operacao'] == '0' ? 'ENTRADA' : 'SAÍDA';
        $tipoDoc = $data['modelo'] == '65' ? 'NFC-e' : 'NF-e';

        $html .= <<<HTML
        <div class="title text-center">{$tipoDoc} {$tipoOperacao}</div>
        <div class="subtitle text-center">Nº {$data['numero']} - Série {$data['serie']}</div>
        
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Data Emissão:</div>
                <div class="info-value">{$data['data_emissao']}</div>
            </div>
        </div>

        <div class="section-title">DESTINATÁRIO</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value">{$data['destinatario']['nome']}</div>
            </div>
            <div class="info-row">
                <div class="info-label">CNPJ/CPF:</div>
                <div class="info-value">{$data['destinatario']['cnpj']}</div>
            </div>
        </div>

        <div class="section-title">EMITENTE</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">CNPJ:</div>
                <div class="info-value">{$data['emitente']['cnpj']}{$data['emitente']['cpf']}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Endereço:</div>
                <div class="info-value">{$data['emitente']['endereco']}, {$data['emitente']['numero']} - {$data['emitente']['bairro']}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Cidade:</div>
                <div class="info-value">{$data['emitente']['municipio']} - {$data['emitente']['uf']}</div>
            </div>
            <div class="info-row">
                <div class="info-label">IE:</div>
                <div class="info-value">{$data['emitente']['ie']}</div>
            </div>
        </div>
HTML;

        if ($cfg['show_itens']) {
            $qtdItens = $data['quantidade_itens'];
            $destaqueClass = $qtdItens > 1 ? 'quantidade-multiplos' : 'quantidade-unico';
            $destaqueTexto = $qtdItens > 1 ? 'ITENS PARA SEPARAR' : 'ÚNICO ITEM';
            
            $html .= <<<HTML
        <div class="{$destaqueClass}">
            <div class="num">{$qtdItens}</div>
            <div class="label">{$destaqueTexto}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:15%">Código</th>
                    <th style="width:40%">Descrição</th>
                    <th style="width:10%" class="text-center">Qtd</th>
                    <th style="width:10%">Un</th>
HTML;

            if ($cfg['show_valor_itens']) {
                $html .= <<<HTML
                    <th style="width:12%" class="text-right">Vl. Unit</th>
                    <th style="width:13%" class="text-right">Vl. Total</th>
HTML;
            }

            $html .= <<<HTML
                </tr>
            </thead>
            <tbody>
HTML;

            foreach ($data['itens'] as $item) {
                $html .= <<<HTML
                <tr>
                    <td>{$item['codigo']}</td>
                    <td>{$item['descricao']}</td>
                    <td class="text-center">{$item['quantidade']}</td>
                    <td>{$item['unidade']}</td>
HTML;

                if ($cfg['show_valor_itens']) {
                    $html .= <<<HTML
                    <td class="text-right">R$ {$item['valor_unitario']}</td>
                    <td class="text-right">R$ {$item['valor_total']}</td>
HTML;
                }

                $html .= '</tr>';
            }

            $html .= <<<HTML
            </tbody>
        </table>
HTML;
        }

        if ($cfg['show_valor_total']) {
            $html .= <<<HTML
        <div class="total-section">
            <div class="total-row">
                <span>Produtos:</span>
                <span>R$ {$data['totais']['produtos']}</span>
            </div>
HTML;

            if ($data['totais']['frete'] > 0) {
                $html .= <<<HTML
            <div class="total-row">
                <span>Frete:</span>
                <span>R$ {$data['totais']['frete']}</span>
            </div>
HTML;
            }

            if ($data['totais']['desconto'] > 0) {
                $html .= <<<HTML
            <div class="total-row">
                <span>Desconto:</span>
                <span>R$ {$data['totais']['desconto']}</span>
            </div>
HTML;
            }

            if ($data['totais']['icms'] > 0) {
                $html .= <<<HTML
            <div class="total-row">
                <span>ICMS:</span>
                <span>R$ {$data['totais']['icms']}</span>
            </div>
HTML;
            }

            $html .= <<<HTML
            <div class="total-row total-final">
                <span>TOTAL:</span>
                <span>R$ {$data['totais']['total']}</span>
            </div>
        </div>
HTML;
        }

        $chave = $data['chave'] ?? '';
        $barcodeData = $this->generateBarcode($chave);
        
        $html .= <<<HTML
        <div class="chave-box">
            <strong>Chave de Acesso:</strong><br>
            {$chave}
        </div>
HTML;

        if ($barcodeData) {
            $html .= <<<HTML
        <div style="text-align: center; padding: 10px 5px; background: #fff;">
            <img src="{$barcodeData}" alt="Barcode NF-e" style="max-width: 100%; height: 30px;">
        </div>
HTML;
        }

        if (isset($data['informacoes_complementares'])) {
            $html .= <<<HTML
        <div class="section-title">INFORMAÇÕES COMPLEMENTARES</div>
        <div style="font-size: 9px;">{$data['informacoes_complementares']}</div>
HTML;
        }

        if ($cfg['rodape']) {
            $html .= <<<HTML
        <div class="footer">
            {$cfg['rodape']}
        </div>
HTML;
        }

        $html .= <<<HTML
    </div>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
HTML;

        return $html;
    }
}
