<?php

namespace App\Http\Controllers;

use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Recebe webhooks do Mercado Livre
     */
    public function meli(Request $request)
    {
        $topic = $request->input('topic');
        $resource = $request->input('resource');
        $userId = $request->input('user_id');

        Log::info("Webhook ML recebido: topic={$topic}, user_id={$userId}, resource={$resource}");

        $payload = $request->all();
        $payload['user_id'] = $userId;

        $externalId = null;

        if ($resource) {
            if (preg_match('/\/orders\/(\d+)/', $resource, $matches)) {
                $externalId = $matches[1];
            } elseif (preg_match('/\/shipments\/(\d+)/', $resource, $matches)) {
                $externalId = $matches[1];
            } elseif (preg_match('/\/items\/(\d+)/', $resource, $matches)) {
                $externalId = $matches[1];
            } elseif (preg_match('/\/questions\/(\d+)/', $resource, $matches)) {
                $externalId = $matches[1];
            } elseif (preg_match('/\/payments\/(\d+)/', $resource, $matches)) {
                $externalId = $matches[1];
            } elseif (preg_match('/\/messages\/(\d+)/', $resource, $matches)) {
                $externalId = $matches[1];
            }
        }

        // Passa informações adicionais do request
        WebhookService::receive(
            'mercadolivre', 
            $topic, 
            $payload, 
            $externalId,
            $request->ip(),
            $request->userAgent(),
            $request->headers->all(),
            $resource
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Recebe webhooks do Bling
     */
    public function bling(Request $request)
    {
        $payload = $request->all();

        $tipo = $payload['tipo'] ?? 'unknown';
        $recurso = $payload['recurso'] ?? null;

        Log::info("Webhook Bling recebido: tipo={$tipo}");

        $topic = $tipo;
        $externalId = null;

        if ($recurso && isset($recurso['id'])) {
            $externalId = $recurso['id'];
        } elseif (isset($payload['id'])) {
            $externalId = $payload['id'];
        } elseif (isset($payload['codigo'])) {
            $externalId = $payload['codigo'];
        }

        WebhookService::receive(
            'bling', 
            $topic, 
            $payload, 
            $externalId,
            $request->ip(),
            $request->userAgent(),
            $request->headers->all(),
            null
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Recebe webhooks genéricos (para outras plataformas)
     */
    public function generic(Request $request, string $source)
    {
        $payload = $request->all();

        Log::info("Webhook genérico recebido: source={$source}");

        $topic = $request->input('topic') ?? $request->input('type') ?? 'unknown';
        $externalId = $request->input('id') ?? $request->input('order_id') ?? null;

        WebhookService::receive(
            $source, 
            $topic, 
            $payload, 
            $externalId,
            $request->ip(),
            $request->userAgent(),
            $request->headers->all(),
            null
        );

        return response()->json(['status' => 'ok']);
    }

    /**
     * Health check para webhooks
     */
    public function health()
    {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    }
}
