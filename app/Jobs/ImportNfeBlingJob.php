<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\Tarefa;
use App\Services\BlingIntegrationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportNfeBlingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $empresaId,
        public int $userId,
        public int $tarefaId,
        public string $dataDe,
        public string $dataAte
    ) {}

    public function handle(): void
    {
        $tarefa = Tarefa::find($this->tarefaId);
        $service = new BlingIntegrationService($this->empresaId);

        if (! $service->isConnected()) {
            Log::warning("ImportNfeBlingJob: Bling não conectado para empresa {$this->empresaId}");
            if ($tarefa) {
                $tarefa->update([
                    'status' => 'falhou',
                    'mensagem' => 'Bling não conectado',
                    'finished_at' => now(),
                ]);
            }

            return;
        }

        if ($tarefa) {
            $tarefa->update([
                'status' => 'processando',
                'mensagem' => 'Iniciando importação de NFes do Bling...',
            ]);
        }

        Log::info("Iniciando ImportNfeBlingJob para empresa {$this->empresaId}, período: {$this->dataDe} a {$this->dataAte}");

        $imported = 0;
        $errors = 0;
        $page = 1;

        do {
            $result = $service->getNotasFiscais([
                'dataEmissaoInicial' => $this->dataDe.' 00:00:00',
                'dataEmissaoFinal' => $this->dataAte.' 23:59:59',
                'pagina' => $page,
                'limite' => 50,
                'tipo' => 1,
                'situacao' => 5,
            ]);

            if (! $result || ! isset($result['data'])) {
                Log::info("ImportNfeBlingJob: Sem mais notas na página {$page}");
                break;
            }

            $notas = $result['data'];

            foreach ($notas as $nota) {
                try {
                    $processResult = $this->processNotaFiscal($nota, $this->empresaId);

                    if ($processResult['success']) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("ImportNfeBlingJob: Erro ao processar nota {$nota['id']}: ".$e->getMessage());
                }
            }

            if ($tarefa) {
                $tarefa->update([
                    'processado' => $page * 50,
                    'sucesso' => $imported,
                    'falha' => $errors,
                    'mensagem' => "Processando página {$page}...",
                ]);
            }

            $page++;
        } while (count($notas) === 50);

        $status = $errors > 0 ? 'concluido_com_erros' : 'concluido';
        $mensagem = "Importadas: {$imported}, Erros: {$errors}";

        if ($tarefa) {
            $tarefa->update([
                'processado' => $imported + $errors,
                'total' => $imported + $errors,
                'sucesso' => $imported,
                'falha' => $errors,
                'status' => $status,
                'mensagem' => $mensagem,
                'finished_at' => now(),
            ]);
        }

        Log::info("ImportNfeBlingJob concluído. Importadas: {$imported}, Erros: {$errors}");
    }

    protected function processNotaFiscal(array $nota, int $empresaId): array
    {
        $numero = $nota['numero'] ?? null;
        $serie = $nota['serie'] ?? 1;
        $chave = $nota['chaveAcesso'] ?? null;

        if (! $numero) {
            return ['success' => false, 'reason' => 'Número da nota não encontrado'];
        }

        if ($chave) {
            $existing = NfeEmitida::where('empresa_id', $empresaId)
                ->where('chave', $chave)
                ->exists();

            if ($existing) {
                return ['success' => false, 'reason' => 'NF-e já importada anteriormente'];
            }
        }

        $dataEmissao = isset($nota['dataEmissao'])
            ? Carbon::parse($nota['dataEmissao'])
            : now();

        $cliente = $nota['cliente'] ?? [];
        $emitente = $nota['emitente'] ?? [];

        $nome = $cliente['nome'] ?? $emitente['nome'] ?? 'Consumidor';
        $cpfCnpj = $cliente['cnpj'] ?? $cliente['cpf'] ?? $emitente['cnpj'] ?? null;
        $endereco = $cliente['endereco'] ?? '';
        $cidade = $cliente['cidade'] ?? '';
        $estado = $cliente['uf'] ?? '';
        $cep = $cliente['cep'] ?? '';

        $valorTotal = floatval($nota['total'] ?? $nota['valorTotal'] ?? 0);
        $valorFrete = floatval($nota['frete'] ?? 0);

        $chaveGerada = $chave ?? $this->generateChave($empresaId, $numero, $serie, $dataEmissao);

        NfeEmitida::create([
            'empresa_id' => $empresaId,
            'chave' => $chaveGerada,
            'numero' => $numero,
            'serie' => $serie,
            'cliente_nome' => $nome,
            'cliente_cnpj' => preg_replace('/[^0-9]/', '', $cpfCnpj ?? ''),
            'cliente_endereco' => $endereco,
            'cliente_cidade' => $cidade,
            'cliente_estado' => $estado,
            'cliente_cep' => $cep,
            'valor_total' => $valorTotal,
            'valor_frete' => $valorFrete,
            'data_emissao' => $dataEmissao,
            'status_nfe' => 'pendente',
            'marketplace' => 'bling',
            'pedido_marketplace' => $nota['pedido'] ?? null,
        ]);

        return ['success' => true];
    }

    protected function generateChave(int $empresaId, int $numero, int $serie, Carbon $data): string
    {
        $empresa = Empresa::find($empresaId);
        $cnpj = preg_replace('/[^0-9]/', '', $empresa?->cnpj ?? '00000000000000');

        $uf = substr($empresa?->cidade?->estado?->uf ?? '91', 0, 2);
        $ano = $data->format('y');
        $mes = $data->format('m');

        $chaveBase = $uf.$ano.$mes.$cnpj.'55'.str_pad($numero, 9, '0', STR_PAD_LEFT).str_pad($serie, 3, '0', STR_PAD_LEFT).'1'.'0';

        $dv = $this->calculateDV($chaveBase);

        return $chaveBase.$dv;
    }

    protected function calculateDV(string $chave): int
    {
        $weights = [2, 3, 4, 5, 6, 7, 8, 9];
        $sum = 0;
        $index = 0;

        for ($i = strlen($chave) - 1; $i >= 0; $i--) {
            $sum += intval($chave[$i]) * $weights[$index % 8];
            $index++;
        }

        $rest = $sum % 11;

        return $rest < 10 ? $rest : 0;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ImportNfeBlingJob falhou para empresa {$this->empresaId}: ".$exception->getMessage());

        $tarefa = Tarefa::find($this->tarefaId);
        if ($tarefa) {
            $tarefa->update([
                'status' => 'falhou',
                'mensagem' => 'Erro: '.$exception->getMessage(),
                'finished_at' => now(),
            ]);
        }
    }
}
