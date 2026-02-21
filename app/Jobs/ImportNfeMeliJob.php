<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Models\Tarefa;
use App\Services\MeliIntegrationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportNfeMeliJob implements ShouldQueue
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
        $service = new MeliIntegrationService($this->empresaId);

        if (! $service->isConnected()) {
            Log::warning("ImportNfeMeliJob: Meli não conectado para empresa {$this->empresaId}");
            if ($tarefa) {
                $tarefa->update([
                    'status' => 'falhou',
                    'mensagem' => 'Mercado Livre não conectado',
                    'finished_at' => now(),
                ]);
            }

            return;
        }

        if ($tarefa) {
            $tarefa->update([
                'status' => 'processando',
                'mensagem' => 'Iniciando importação de NFes...',
            ]);
        }

        Log::info("Iniciando ImportNfeMeliJob para empresa {$this->empresaId}, período: {$this->dataDe} a {$this->dataAte}");

        $imported = 0;
        $errors = 0;
        $page = 0;
        $limit = 50;
        $details = [];

        $dataDe = Carbon::parse($this->dataDe)->startOfDay();
        $dataAte = Carbon::parse($this->dataAte)->endOfDay();

        do {
            $result = $service->getOrders([
                'limit' => $limit,
                'offset' => $page * $limit,
            ]);

            if (isset($result['error'])) {
                Log::error("ImportNfeMeliJob: Erro na página {$page}: {$result['error']}");
                if ($tarefa) {
                    $tarefa->update([
                        'status' => 'falhou',
                        'mensagem' => $result['error'],
                        'finished_at' => now(),
                    ]);
                }
                break;
            }

            $orders = $result['results'] ?? [];

            if (empty($orders)) {
                break;
            }

            foreach ($orders as $order) {
                $orderDate = isset($order['date_created'])
                    ? Carbon::parse($order['date_created'])->startOfDay()
                    : null;

                if ($orderDate && ($orderDate->lt($dataDe) || $orderDate->gt($dataAte))) {
                    continue;
                }

                try {
                    $processResult = $this->processOrderForNfe($order, $this->empresaId);

                    if ($processResult['success']) {
                        $imported++;
                        $details[] = "Pedido {$order['id']} - NF importada";
                    } else {
                        $errors++;
                        $details[] = "Pedido {$order['id']}: {$processResult['reason']}";
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $details[] = "Pedido {$order['id']}: {$e->getMessage()}";
                    Log::error("ImportNfeMeliJob: Erro ao processar pedido {$order['id']}: ".$e->getMessage());
                }
            }

            if ($tarefa) {
                $tarefa->update([
                    'processado' => ($page + 1) * $limit,
                    'sucesso' => $imported,
                    'falha' => $errors,
                    'mensagem' => "Processando página {$page}...",
                ]);
            }

            $page++;
        } while (count($orders) === $limit);

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

        Log::info("ImportNfeMeliJob concluído. Importadas: {$imported}, Erros: {$errors}");
    }

    protected function processOrderForNfe(array $order, int $empresaId): array
    {
        $status = $order['status'] ?? '';

        if (! in_array($status, ['paid', 'completed', 'released'])) {
            return ['success' => false, 'reason' => 'Status do pedido não é paid/completed'];
        }

        $orderId = $order['id'];
        $meliService = new MeliIntegrationService($empresaId);

        $orderDetail = $meliService->getOrderDetail($orderId);

        if (isset($orderDetail['error'])) {
            return ['success' => false, 'reason' => 'Não foi possível obter detalhes do pedido'];
        }

        if (! $orderDetail) {
            return ['success' => false, 'reason' => 'Detalhes do pedido não encontrados'];
        }

        $billingInfo = $meliService->getBillingInfo($orderId);
        $billingData = null;
        if (! isset($billingInfo['error']) && ! empty($billingInfo)) {
            $billingData = $billingInfo;
        }

        $buyer = $orderDetail['buyer'] ?? [];
        $shippingAddress = $orderDetail['shipping_address'] ?? ($orderDetail['shipping']['shipping_address'] ?? []);

        $nome = $this->extractNome($buyer, $billingData);
        $cpfCnpj = $this->extractCpfCnpj($buyer, $billingData);
        $endereco = $this->extractEndereco($billingData, $shippingAddress);
        $cidade = $this->extractCidade($billingData);
        $estado = $this->extractEstado($billingData);
        $cep = $this->extractCep($billingData, $shippingAddress);

        $numero = $orderDetail['id'];
        $serie = 1;

        $valorTotal = floatval($orderDetail['total_amount'] ?? $orderDetail['amount'] ?? 0);
        $valorFrete = floatval($orderDetail['shipping_cost'] ?? 0);

        $dataEmissao = isset($orderDetail['date_created'])
            ? Carbon::parse($orderDetail['date_created'])
            : now();

        $chave = $this->generateChave($empresaId, $numero, $serie, $dataEmissao);

        $existing = NfeEmitida::where('empresa_id', $empresaId)
            ->where('chave', $chave)
            ->exists();

        if ($existing) {
            return ['success' => false, 'reason' => 'NF-e já importada anteriormente'];
        }

        NfeEmitida::create([
            'empresa_id' => $empresaId,
            'chave' => $chave,
            'numero' => $numero,
            'serie' => $serie,
            'cliente_nome' => $nome,
            'cliente_cnpj' => $cpfCnpj,
            'cliente_endereco' => $endereco,
            'cliente_cidade' => $cidade,
            'cliente_estado' => $estado,
            'cliente_cep' => $cep,
            'valor_total' => $valorTotal,
            'valor_frete' => $valorFrete,
            'data_emissao' => $dataEmissao,
            'status_nfe' => 'pendente',
            'marketplace' => 'mercadolivre',
            'pedido_marketplace' => $orderId,
        ]);

        return ['success' => true];
    }

    protected function extractNome(array $buyer, ?array $billingData): string
    {
        if ($billingData && isset($billingData['billing_info']['name'])) {
            return $billingData['billing_info']['name'];
        }

        $nome = trim(($buyer['first_name'] ?? '').' '.($buyer['last_name'] ?? ''));

        return $nome ?: 'Consumidor Final';
    }

    protected function extractCpfCnpj(array $buyer, ?array $billingData): ?string
    {
        if ($billingData && isset($billingData['billing_info']['identification'])) {
            $docNumber = $billingData['billing_info']['identification']['number'] ?? null;
            if ($docNumber) {
                return preg_replace('/[^0-9]/', '', $docNumber);
            }
        }

        if (isset($buyer['billing_info']['doc_number'])) {
            return preg_replace('/[^0-9]/', '', $buyer['billing_info']['doc_number']);
        }

        return null;
    }

    protected function extractEndereco(?array $billingData, array $shippingAddress): string
    {
        if ($billingData && isset($billingData['billing_info']['address'])) {
            $addr = $billingData['billing_info']['address'];
            $parts = [];
            if (! empty($addr['street_name'])) {
                $parts[] = $addr['street_name'];
            }
            if (! empty($addr['street_number'])) {
                $parts[] = $addr['street_number'];
            }
            if (! empty($addr['comment'])) {
                $parts[] = $addr['comment'];
            }
            if (! empty($parts)) {
                return implode(', ', $parts);
            }
        }

        $addressLine = $shippingAddress['address_line'] ?? '';
        $streetNumber = $shippingAddress['street_number'] ?? '';

        if ($addressLine && $streetNumber) {
            return $addressLine.', '.$streetNumber;
        }

        return $addressLine ?: $streetNumber;
    }

    protected function extractCidade(?array $billingData): string
    {
        if ($billingData && isset($billingData['billing_info']['address']['city_name'])) {
            return $billingData['billing_info']['address']['city_name'];
        }

        return '';
    }

    protected function extractEstado(?array $billingData): string
    {
        if ($billingData && isset($billingData['billing_info']['address']['state'])) {
            return is_array($billingData['billing_info']['address']['state'])
                ? ($billingData['billing_info']['address']['state']['name'] ?? '')
                : $billingData['billing_info']['address']['state'];
        }

        return '';
    }

    protected function extractCep(?array $billingData, array $shippingAddress): string
    {
        if ($billingData && isset($billingData['billing_info']['address']['zip_code'])) {
            return $billingData['billing_info']['address']['zip_code'];
        }

        return $shippingAddress['zip_code'] ?? '';
    }

    protected function extractCpfCnpjOnly(array $buyer): ?string
    {
        if (isset($buyer['billing_info']['doc_number'])) {
            return preg_replace('/[^0-9]/', '', $buyer['billing_info']['doc_number']);
        }

        return null;
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
        Log::error("ImportNfeMeliJob falhou para empresa {$this->empresaId}: ".$exception->getMessage());

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
