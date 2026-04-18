<?php

namespace App\Console\Commands;

use App\Models\Integracao;
use App\Services\MeliIntegrationService;
use Illuminate\Console\Command;

class RegistrarWebhooksMeli extends Command
{
    protected $signature = 'meli:registrar-webhooks {--empresa= : ID da empresa}';

    protected $description = 'Registra os webhooks do Mercado Livre para receber pedidos e notificações';

    public function handle(): int
    {
        $empresaId = $this->option('empresa');

        $query = Integracao::where('marketplace', 'mercadolivre')
            ->where('ativo', true);

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $integracoes = $query->get();

        if ($integracoes->isEmpty()) {
            $this->error('Nenhuma integração com Mercado Livre encontrada.');

            return Command::FAILURE;
        }

        foreach ($integracoes as $integracao) {
            $this->info("Registrando webhooks para empresa {$integracao->empresa_id}...");

            $service = new MeliIntegrationService($integracao->empresa_id);
            $result = $service->registerWebhooks();

            foreach ($result as $topic => $response) {
                if ($response['success'] ?? false) {
                    $this->info("  ✓ Webhook {$topic} registrado com sucesso");
                } else {
                    $this->warn("  ✗ Erro ao registrar {$topic}: ".json_encode($response['error'] ?? 'Erro desconhecido'));
                }
            }

            $this->info("Webhooks listados para empresa {$integracao->empresa_id}:");
            $list = $service->listWebhooks();
            if (isset($list['results'])) {
                foreach ($list['results'] as $webhook) {
                    $this->line("  - ID: {$webhook['id']}, Topic: {$webhook['topic']}, URL: {$webhook['url']}");
                }
            } else {
                $this->warn('  Não foi possível listar os webhooks: '.json_encode($list));
            }
        }

        $this->info('Registro de webhooks concluído!');

        return Command::SUCCESS;
    }
}
