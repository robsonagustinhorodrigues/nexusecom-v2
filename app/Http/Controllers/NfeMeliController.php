<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\NfeEmitida;
use App\Services\MeliIntegrationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NfeMeliController extends Controller
{
    protected array $erros = [];

    public function syncNfeEmitidas(Request $request)
    {
        $empresaId = Auth::user()->current_empresa_id;
        $empresa = Empresa::findOrFail($empresaId);

        $dataDe = $request->get('dataDe', now()->subDays(90)->format('Y-m-d'));
        $dataAte = $request->get('dataAte', now()->format('Y-m-d'));

        $service = new MeliIntegrationService($empresaId);

        if (! $service->isConnected()) {
            return redirect()->route('orders.index')->with('error', 'Mercado Livre não conectado.');
        }

        $this->erros = [];

        try {
            $result = $this->importNfeFromOrders($service, $empresaId, $dataDe, $dataAte);

            $this->salvarLogImportacao($empresaId, $result['imported'], $result['errors'], $result['details']);

            if (! empty($result['errors'])) {
                return redirect()->route('orders.index')->with('warning',
                    "{$result['imported']} importadas, {$result['errors']} com erro. Verifique o log."
                );
            }

            return redirect()->route('orders.index')->with('message', $result['message']);
        } catch (\Exception $e) {
            Log::error('Erro ao importar NFes do Meli: '.$e->getMessage());

            return redirect()->route('orders.index')->with('error', 'Erro ao importar: '.$e->getMessage());
        }
    }

    protected function salvarLogImportacao(int $empresaId, int $sucesso, int $falha, array $detalhes): void
    {
        $logContent = "LOG DE IMPORTAÇÃO DE NF-e (Mercado Livre)\n";
        $logContent .= 'Data: '.Carbon::now()->format('d/m/Y H:i:s')."\n";
        $logContent .= 'Empresa ID: '.$empresaId."\n";
        $logContent .= 'Total processados: '.($sucesso + $falha)."\n";
        $logContent .= str_repeat('-', 60)."\n\n";

        if (empty($detalhes)) {
            $logContent .= "Nenhum detalhe disponível.\n";
        } else {
            foreach ($detalhes as $detail) {
                $tipo = $detail['tipo'] ?? 'INFO';
                $mensagem = $detail['mensagem'] ?? '';
                $logContent .= "[{$tipo}] {$mensagem}\n";
            }
        }

        $logContent .= "\n".str_repeat('-', 60)."\n";
        $logContent .= "RESUMO\n";
        $logContent .= "Sucesso: {$sucesso}\n";
        $logContent .= "Falhas: {$falha}\n";
        $logContent .= 'Data término: '.Carbon::now()->format('d/m/Y H:i:s')."\n";

        $logFileName = 'importacao_nfe_meli_'.$empresaId.'_'.time().'.log';
        $logPath = 'logs/importacao/'.$logFileName;

        try {
            if (! Storage::exists('logs/importacao')) {
                Storage::makeDirectory('logs/importacao');
            }
            Storage::put($logPath, $logContent);
            Log::info("Log de importação NF-e Meli salvo: {$logPath}");
        } catch (\Exception $e) {
            Log::error('Erro ao salvar log de importação: '.$e->getMessage());
        }
    }

    protected function importNfeFromOrders(MeliIntegrationService $service, int $empresaId, string $dataDe, string $dataAte): array
    {
        $page = 0;
        $imported = 0;
        $errors = 0;
        $details = [];
        $limit = 50;

        do {
            $result = $service->getOrders([
                'limit' => $limit,
                'offset' => $page * $limit,
                'date_created_from' => $dataDe.'T00:00:00.000-03:00',
                'date_created_to' => $dataAte.'T23:59:59.000-03:00',
            ]);

            if (isset($result['error'])) {
                $details[] = ['tipo' => 'ERRO', 'mensagem' => 'Erro ao buscar pedidos: '.$result['error']];

                return ['success' => false, 'message' => $result['error'], 'imported' => $imported, 'errors' => $errors, 'details' => $details];
            }

            $orders = $result['results'] ?? [];

            foreach ($orders as $order) {
                try {
                    $processResult = $this->processOrderForNfe($order, $empresaId);

                    if ($processResult['success']) {
                        $imported++;
                        $details[] = [
                            'tipo' => 'OK',
                            'mensagem' => "Pedido {$order['id']} - NF importada com sucesso",
                        ];
                    } else {
                        $errors++;
                        $details[] = [
                            'tipo' => 'ERRO',
                            'mensagem' => "Pedido {$order['id']}: {$processResult['reason']}",
                        ];
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $details[] = [
                        'tipo' => 'ERRO',
                        'mensagem' => "Pedido {$order['id']}: {$e->getMessage()}",
                    ];
                    Log::error("Erro ao processar pedido {$order['id']}: ".$e->getMessage());
                }
            }

            $page++;
        } while (count($orders) === $limit);

        return ['success' => true, 'message' => "{$imported} NFes importadas", 'imported' => $imported, 'errors' => $errors, 'details' => $details];
    }

    protected function processOrderForNfe(array $order, int $empresaId): array
    {
        $status = $order['status'] ?? '';

        if (! in_array($status, ['paid', 'completed'])) {
            return ['success' => false, 'reason' => 'Status do pedido não é paid/completed'];
        }

        $orderId = $order['id'];

        $orderDetail = null;

        if (isset($order['order_invoice_id'])) {
            $orderDetail = $order;
        } else {
            $meliService = new MeliIntegrationService($empresaId);
            $orderDetail = $meliService->getOrderDetail($orderId);

            if (isset($orderDetail['error'])) {
                Log::warning("Pedido {$orderId}: não foi possível obter detalhes");

                return ['success' => false, 'reason' => 'Não foi possível obter detalhes do pedido'];
            }
        }

        if (! $orderDetail) {
            return ['success' => false, 'reason' => 'Detalhes do pedido não encontrados'];
        }

        $buyer = $orderDetail['buyer'] ?? [];
        $shipping = $orderDetail['shipping'] ?? [];
        $shippingAddress = $orderDetail['shipping_address'] ?? ($shipping['shipping_address'] ?? []);

        $nome = ($buyer['first_name'] ?? '').' '.($buyer['last_name'] ?? '');
        $cpfCnpj = $this->extractCpfCnpj($buyer);

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
            'cliente_endereco' => ($shippingAddress['address_line'] ?? '').', '.($shippingAddress['street_number'] ?? ''),
            'cliente_cidade' => is_array($shippingAddress['city'] ?? null) ? ($shippingAddress['city']['name'] ?? '') : ($shippingAddress['city'] ?? ''),
            'cliente_estado' => is_array($shippingAddress['state'] ?? null) ? ($shippingAddress['state']['name'] ?? '') : ($shippingAddress['state'] ?? ''),
            'cliente_cep' => $shippingAddress['zip_code'] ?? '',
            'valor_total' => $valorTotal,
            'valor_frete' => $valorFrete,
            'data_emissao' => $dataEmissao,
            'status_nfe' => 'pendente',
            'marketplace' => 'mercadolivre',
            'pedido_marketplace' => $orderId,
        ]);

        return ['success' => true];
    }

    protected function extractCpfCnpj(array $buyer): ?string
    {
        if (isset($buyer['billing_info']['doc_number'])) {
            $doc = $buyer['billing_info']['doc_number'];

            return preg_replace('/[^0-9]/', '', $doc);
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
}
