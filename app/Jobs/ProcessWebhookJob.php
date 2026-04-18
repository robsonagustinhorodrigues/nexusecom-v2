<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Services\MeliIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public int $webhookId
    ) {}

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        if (! $webhook) {
            Log::warning("ProcessWebhookJob: Webhook #{$this->webhookId} não encontrado");

            return;
        }

        if ($webhook->status === 'processed') {
            Log::info("ProcessWebhookJob: Webhook #{$this->webhookId} já processado");

            return;
        }

        $webhook->markAsProcessing();

        try {
            match ($webhook->source) {
                'mercadolivre' => $this->processMeli($webhook),
                'bling' => $this->processBling($webhook),
                'shopee' => $this->processShopee($webhook),
                'amazon' => $this->processAmazon($webhook),
                'magalu' => $this->processMagalu($webhook),
                default => Log::warning("ProcessWebhookJob: Source desconhecido: {$webhook->source}")
            };

            $webhook->markAsProcessed();
            Log::info("ProcessWebhookJob: Webhook #{$this->webhookId} processado com sucesso");

        } catch (\Exception $e) {
            $webhook->markAsFailed($e->getMessage());
            Log::error("ProcessWebhookJob: Erro ao processar webhook #{$this->webhookId}: ".$e->getMessage());
            throw $e;
        }
    }

    protected function processMeli(Webhook $webhook): void
    {
        $payload = $webhook->payload;
        $topic = $webhook->topic;

        Log::info("ProcessWebhookJob: Processando ML topic: {$topic}");

        match ($topic) {
            'orders', 'orders_v2', 'order_created', 'order_updated' => $this->processMeliOrder($webhook),
            'shipments', 'shipment_updated' => $this->processMeliShipment($webhook),
            'inventory', 'items' => $this->processMeliInventory($webhook),
            'questions' => $this->processMeliQuestion($webhook),
            'payments' => $this->processMeliPayment($webhook),
            'messages' => $this->processMeliMessage($webhook),
            default => Log::info("ProcessWebhookJob: Topic ML não tratado: {$topic}")
        };
    }

    protected function processMeliOrder(Webhook $webhook): void
    {
        $empresaId = $webhook->empresa_id;
        $externalId = $webhook->external_id;

        if (! $externalId) {
            return;
        }

        $service = new MeliIntegrationService($empresaId);

        if (! $service->isConnected()) {
            Log::warning("ProcessWebhookJob: Meli não conectado para empresa {$empresaId}");

            return;
        }

        $order = $service->getOrderDetail($externalId);

        if (isset($order['error'])) {
            Log::error("ProcessWebhookJob: Erro ao buscar pedido ML {$externalId}: ".$order['error']);

            return;
        }

        $service->importOrder($order);
        Log::info("ProcessWebhookJob: Pedido ML {$externalId} importado/atualizado");

        // Verificar se tem valor de frete e notificar
        $frete = $order['shipping']['shipping_option']['cost'] ?? 0;
        if ($frete > 0) {
            $empresa = \App\Models\Empresa::find($empresaId);
            $nomeEmpresa = $empresa?->nome ?? 'Empresa';

            \App\Models\Notificacao::criar(
                'frete',
                'Novo Pedido com Frete',
                "Pedido {$externalId} - Frete: R$ " . number_format($frete, 2, ',', '.') . " ({$nomeEmpresa})",
                'info',
                '/orders'
            );
        }
    }

    protected function processMeliShipment(Webhook $webhook): void
    {
        $externalId = $webhook->external_id;

        Log::info("ProcessWebhookJob: Shipment ML {$externalId} atualizado (atualização de status apenas)");
    }

    protected function processMeliInventory(Webhook $webhook): void
    {
        Log::info('ProcessWebhookJob: Inventory ML atualizado');
    }

    protected function processMeliQuestion(Webhook $webhook): void
    {
        Log::info("ProcessWebhookJob: Question ML {$webhook->external_id} atualizada");
    }

    protected function processMeliPayment(Webhook $webhook): void
    {
        Log::info("ProcessWebhookJob: Payment ML {$webhook->external_id} atualizado");
    }

    protected function processMeliMessage(Webhook $webhook): void
    {
        Log::info("ProcessWebhookJob: Message ML {$webhook->external_id} recebido");
    }

    protected function processBling(Webhook $webhook): void
    {
        $payload = $webhook->payload;
        $topic = $webhook->topic;

        Log::info("ProcessWebhookJob: Processando Bling topic: {$topic}");

        match ($topic) {
            'pedido.venda', 'pedido.venda.cadastrado', 'pedido.venda.alterado' => $this->processBlingOrder($webhook),
            'nota.saida', 'nota.fiscal' => $this->processBlingNfe($webhook),
            'produto' => $this->processBlingProduct($webhook),
            default => Log::info("ProcessWebhookJob: Topic Bling não tratado: {$topic}")
        };
    }

    protected function processBlingOrder(Webhook $webhook): void
    {
        $empresaId = $webhook->empresa_id;
        $externalId = $webhook->external_id;

        if (! $externalId) {
            return;
        }

        Log::info("ProcessWebhookJob: Pedido Bling {$externalId} atualizado");
    }

    protected function processBlingNfe(Webhook $webhook): void
    {
        $externalId = $webhook->external_id;

        if (! $externalId) {
            return;
        }

        Log::info("ProcessWebhookJob: NFe Bling {$externalId} atualizada");
    }

    protected function processBlingProduct(Webhook $webhook): void
    {
        Log::info('ProcessWebhookJob: Produto Bling atualizado');
    }

    protected function processShopee(Webhook $webhook): void
    {
        Log::info("ProcessWebhookJob: Shopee webhook topic: {$webhook->topic}");
    }

    protected function processAmazon(Webhook $webhook): void
    {
        Log::info("ProcessWebhookJob: Amazon webhook topic: {$webhook->topic}");
    }

    protected function processMagalu(Webhook $webhook): void
    {
        Log::info("ProcessWebhookJob: Magalu webhook topic: {$webhook->topic}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessWebhookJob falhou para webhook #{$this->webhookId}: ".$exception->getMessage());
    }
}
