<?php

namespace App\Console\Commands\Fiscal;

use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\NfeRecebida;
use App\Models\NfeItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class NormalizeNfeClassification extends Command
{
    protected $signature = 'fiscal:normalize-types {--empresa= : ID da empresa específica} {--dry-run : Apenas simula as alterações}';
    protected $description = 'Corrige a classificação de Entrada/Saída e Emitida/Recebida de todas as NFes baseada no XML';

    public function handle()
    {
        $empresaId = $this->option('empresa');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('MODO SIMULAÇÃO ATIVADO - Nenhuma alteração será persistida.');
        }

        $this->info('Iniciando normalização de NF-es...');

        if ($empresaId) {
            $empresas = Empresa::where('id', $empresaId)->get();
        } else {
            $empresas = Empresa::all();
        }

        foreach ($empresas as $empresa) {
            $this->info("Processando Empresa: {$empresa->nome} (CNPJ: {$empresa->cnpj})");
            
            $this->processTable(NfeEmitida::class, $empresa, $dryRun);
            $this->processTable(NfeRecebida::class, $empresa, $dryRun);
        }

        $this->info('Normalização concluída!');
    }

    private function processTable($modelClass, $empresa, $dryRun)
    {
        $records = $modelClass::where('empresa_id', $empresa->id)->get();
        $this->info("  Tabela " . (new $modelClass)->getTable() . ": {$records->count()} registros.");

        foreach ($records as $nfe) {
            if (!$nfe->xml_path || !Storage::exists($nfe->xml_path)) {
                $this->error("    [ERRO] XML não encontrado para chave {$nfe->chave}");
                continue;
            }

            try {
                $xmlContent = Storage::get($nfe->xml_path);
                $xml = simplexml_load_string($xmlContent);
                if (!$xml) throw new \Exception("XML Inválido");

                $infNFe = $xml->NFe->infNFe ?? $xml->infNFe;
                if (!$infNFe) {
                     // Check for procNFe
                     if (isset($xml->procNFe)) {
                         $infNFe = $xml->procNFe->NFe->infNFe ?? $xml->procNFe->infNFe;
                     }
                }
                
                if (!$infNFe) throw new \Exception("Estrutura infNFe não encontrada");

                $tpNF = (int) $infNFe->ide->tpNF;
                $emitCnpj = preg_replace('/[^0-9]/', '', (string) ($infNFe->emit->CNPJ ?: $infNFe->emit->CPF));
                $destCnpj = preg_replace('/[^0-9]/', '', (string) ($infNFe->dest->CNPJ ?: $infNFe->dest->CPF));
                $emitNome = (string) $infNFe->emit->xNome;
                $destNome = (string) $infNFe->dest->xNome;

                $cnpjEmpresa = ltrim(preg_replace('/[^0-9]/', '', $empresa->cnpj), '0');
                $cnpjEmitenteStr = ltrim($emitCnpj, '0');

                // Lógica de Classificação
                $isEmitidaPelaEmpresa = ($cnpjEmitenteStr === $cnpjEmpresa);
                $novaCategoria = $isEmitidaPelaEmpresa ? 'emitida' : 'recebida';
                $novoTipoFiscal = ($novaCategoria === 'emitida') 
                    ? ($tpNF == 0 ? 'entrada' : 'saida')
                    : ($tpNF == 0 ? 'saida' : 'entrada');

                $tabelaAtual = (new $modelClass)->getTable();
                $tabelaCorreta = ($novaCategoria === 'emitida' ? 'nfe_emitidas' : 'nfe_recebidas');

                if ($tabelaAtual !== $tabelaCorreta) {
                    $this->warn("    [MOVER] Chave {$nfe->chave}: {$tabelaAtual} -> {$tabelaCorreta} | Tipo: {$novoTipoFiscal}");
                    if (!$dryRun) {
                        $this->moveNfe($nfe, $novaCategoria, $novoTipoFiscal, $tpNF, $emitCnpj, $emitNome, $destCnpj, $destNome);
                    }
                } else {
                    $this->line("    [UPDATE] Chave {$nfe->chave}: {$novoTipoFiscal}");
                    if (!$dryRun) {
                        $updateData = [
                            'tipo_fiscal' => $novoTipoFiscal,
                            'tp_nf' => $tpNF,
                            'emitente_cnpj' => $emitCnpj,
                            'emitente_nome' => $emitNome,
                            'cliente_cnpj' => $destCnpj,
                            'cliente_nome' => $destNome,
                        ];
                        $nfe->update($updateData);
                    }
                }

            } catch (\Exception $e) {
                $this->error("    [ERRO] Processando chave {$nfe->chave}: " . $e->getMessage());
            }
        }
    }

    private function moveNfe($nfe, $novaCategoria, $novoTipoFiscal, $tpNF, $emitCnpj, $emitNome, $destCnpj, $destNome)
    {
        DB::transaction(function () use ($nfe, $novaCategoria, $novoTipoFiscal, $tpNF, $emitCnpj, $emitNome, $destCnpj, $destNome) {
            $commonData = [
                'empresa_id' => $nfe->empresa_id,
                'chave' => $nfe->chave,
                'numero' => $nfe->numero,
                'serie' => $nfe->serie,
                'valor_total' => $nfe->valor_total,
                'data_emissao' => $nfe->data_emissao,
                'xml_path' => $nfe->xml_path,
                'tipo_fiscal' => $novoTipoFiscal,
                'tp_nf' => $tpNF,
                'emitente_cnpj' => $emitCnpj,
                'emitente_nome' => $emitNome,
                'cliente_cnpj' => $destCnpj,
                'cliente_nome' => $destNome,
                'created_at' => $nfe->created_at,
                'updated_at' => $nfe->updated_at,
            ];

            if ($novaCategoria === 'emitida') {
                $newNfe = NfeEmitida::create(array_merge($commonData, [
                    'status' => $nfe->status_nfe ?? 'autorizada',
                ]));
                // Move items
                NfeItem::where('nfe_recebida_id', $nfe->id)->update([
                    'nfe_recebida_id' => null,
                    'nfe_emitida_id' => $newNfe->id
                ]);
            } else {
                $newNfe = NfeRecebida::create(array_merge($commonData, [
                    'status_nfe' => $nfe->status ?? 'autorizada',
                    'status_manifestacao' => 'sem_manifesto',
                ]));
                // Move items
                NfeItem::where('nfe_emitida_id', $nfe->id)->update([
                    'nfe_emitida_id' => null,
                    'nfe_recebida_id' => $newNfe->id
                ]);
            }

            $nfe->delete();
        });
    }
}
