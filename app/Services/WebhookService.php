<?php

namespace App\Services;

use App\Jobs\ProcessWebhookJob;
use App\Models\Integracao;
use App\Models\Webhook;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public static function receive(
        string $source, 
        string $topic, 
        array $payload, 
        ?string $externalId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $headers = null,
        ?string $resourceUrl = null
    ): ?Webhook
    {
        $empresaId = self::getEmpresaId($source, $payload);

        if (! $empresaId) {
            Log::warning("WebhookService: Empresa não encontrada para source: {$source}");

            // Mesmo sem empresa, cria o registro para debug
            $webhook = Webhook::create([
                'empresa_id' => null,
                'source' => $source,
                'topic' => $topic,
                'external_id' => $externalId ?? ($payload['id'] ?? $payload['order_id'] ?? null),
                'payload' => $payload,
                'status' => 'failed',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'headers' => $headers,
                'resource_url' => $resourceUrl,
                'error' => 'Empresa não encontrada',
            ]);

            return $webhook;
        }

        $webhook = Webhook::create([
            'empresa_id' => $empresaId,
            'source' => $source,
            'topic' => $topic,
            'external_id' => $externalId ?? ($payload['id'] ?? $payload['order_id'] ?? null),
            'payload' => $payload,
            'status' => 'pending',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'headers' => $headers,
            'resource_url' => $resourceUrl,
        ]);

        Log::info("WebhookService: Webhook #{$webhook->id} criado para {$source}/{$topic} (Empresa: {$empresaId})");

        // Dispara o job para processar em background
        ProcessWebhookJob::dispatch($webhook->id);

        return $webhook;
    }

    protected static function getEmpresaId(string $source, array $payload): ?int
    {
        $integracao = match ($source) {
            'mercadolivre' => Integracao::where('marketplace', 'mercadolivre')
                ->where('ativo', true)
                ->where('external_user_id', $payload['user_id'] ?? null)
                ->first(),
            'bling' => Integracao::where('marketplace', 'bling')
                ->where('ativo', true)
                ->first(),
            'shopee' => Integracao::where('marketplace', 'shopee')
                ->where('ativo', true)
                ->first(),
            'amazon' => Integracao::where('marketplace', 'amazon')
                ->where('ativo', true)
                ->first(),
            'magalu' => Integracao::where('marketplace', 'magalu')
                ->where('ativo', true)
                ->first(),
            default => null,
        };

        return $integracao?->empresa_id;
    }

    public static function processPending(int $limit = 50): int
    {
        $webhooks = Webhook::where('status', 'pending')
            ->orderBy('received_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($webhooks as $webhook) {
            ProcessWebhookJob::dispatch($webhook->id);
        }

        return $webhooks->count();
    }

    public static function retryFailed(int $limit = 50): int
    {
        $webhooks = Webhook::where('status', 'failed')
            ->where('attempts', '<', 3)
            ->orderBy('received_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($webhooks as $webhook) {
            $webhook->update(['status' => 'retrying']);
            ProcessWebhookJob::dispatch($webhook->id);
        }

        return $webhooks->count();
    }

    /**
     * Estatísticas de webhooks
     */
    public static function getStats(?int $empresaId = null): array
    {
        $query = Webhook::query();
        
        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'processing' => (clone $query)->where('status', 'processing')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'retrying' => (clone $query)->where('status', 'retrying')->count(),
            'today' => (clone $query)->whereDate('received_at', today())->count(),
        ];
    }
}
