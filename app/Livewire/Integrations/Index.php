<?php

namespace App\Livewire\Integrations;

use App\Models\Integracao;
use App\Services\AmazonSpApiService;
use App\Services\MeliIntegrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public $testResult = null;
    public $testingIntegration = null;

    public function connectMeli()
    {
        return redirect()->route('meli.redirect');
    }

    public function disconnect($id)
    {
        Integracao::where('empresa_id', Auth::user()->current_empresa_id)
            ->where('id', $id)
            ->delete();

        session()->flash('message', 'Integração removida com sucesso.');
    }

    public function testConnection($marketplace)
    {
        $this->testingIntegration = $marketplace;
        $this->testResult = null;

        try {
            $empresaId = Auth::user()->current_empresa_id;

            $result = match ($marketplace) {
                'bling' => $this->testBling($empresaId),
                'meli' => $this->testMeli($empresaId),
                'amazon' => $this->testAmazon($empresaId),
                default => ['success' => false, 'message' => 'Marketplace não suportado']
            };

            $this->testResult = $result;
        } catch (\Exception $e) {
            Log::error("Test connection error ({$marketplace}): " . $e->getMessage());
            $this->testResult = ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }

        $this->testingIntegration = null;
    }

    private function testBling($empresaId)
    {
        $bling = Integracao::where('empresa_id', $empresaId)
            ->where('marketplace', 'bling')
            ->where('ativo', true)
            ->first();

        if (!$bling) {
            return ['success' => false, 'message' => 'Bling não configurado'];
        }

        if (empty($bling->access_token)) {
            return ['success' => false, 'message' => 'Token não encontrado'];
        }

        try {
            // Try to refresh token if needed
            $blingService = new \App\Services\BlingIntegrationService($empresaId);
            $blingService->refreshTokenIfNeeded();
            
            // Re-fetch the integration after potential refresh
            $bling->refresh();
            
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $bling->access_token
            ])->get('https://api.bling.com.br/Api/v3/contatos?limit=1');

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Conectado!'];
            }

            return ['success' => false, 'message' => 'Erro na API: ' . $response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    private function testMeli($empresaId)
    {
        $meli = Integracao::where('empresa_id', $empresaId)
            ->where('marketplace', 'mercadolivre')
            ->where('ativo', true)
            ->first();

        if (!$meli) {
            return ['success' => false, 'message' => 'Mercado Livre não configurado'];
        }

        try {
            $service = new MeliIntegrationService($empresaId);
            
            // Try to get orders (this handles token refresh internally)
            $result = $service->getOrders(['limit' => 1]);

            if (isset($result['error'])) {
                return ['success' => false, 'message' => $result['error']];
            }

            return ['success' => true, 'message' => 'Conectado!'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    private function testAmazon($empresaId)
    {
        $amazon = Integracao::where('empresa_id', $empresaId)
            ->where('marketplace', 'amazon')
            ->where('ativo', true)
            ->first();

        if (!$amazon) {
            return ['success' => false, 'message' => 'Amazon não configurado'];
        }

        try {
            $service = new AmazonSpApiService($empresaId);
            $result = $service->testConnection();

            if ($result['success'] ?? false) {
                return ['success' => true, 'message' => 'Conectado!'];
            }

            return ['success' => false, 'message' => $result['message'] ?? 'Erro na conexão'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.integrations.index', [
            'integracoes' => Integracao::where('empresa_id', Auth::user()->current_empresa_id)
                ->where('ativo', true)
                ->get(),
        ]);
    }
}
