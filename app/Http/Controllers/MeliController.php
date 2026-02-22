<?php

namespace App\Http\Controllers;

use App\Jobs\SyncMeliAnunciosJob;
use App\Models\Integracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeliController extends Controller
{
    /**
     * Redireciona para o login do Mercado Livre
     */
    public function redirect()
    {
        $appId = config('services.meli.app_id');
        $redirectUri = config('services.meli.redirect_uri');

        $url = "https://auth.mercadolivre.com.br/authorization?response_type=code&client_id={$appId}&redirect_uri=".urlencode($redirectUri);

        return redirect()->away($url);
    }

    /**
     * Recebe o código do ML e troca por Access Token
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');

        if (! $code) {
            return redirect()->route('integrations.index')->with('error', 'Falha na autorização do Mercado Livre.');
        }

        try {
            $response = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.meli.app_id'),
                'client_secret' => config('services.meli.app_secret'),
                'code' => $code,
                'redirect_uri' => config('services.meli.redirect_uri'),
            ]);

            if ($response->failed()) {
                throw new \Exception('Erro ML: '.$response->body());
            }

            $data = $response->json();
            $empresaId = Auth::user()->current_empresa_id;

            // Salva ou Atualiza a integração
            $integracao = Integracao::updateOrCreate(
                [
                    'empresa_id' => $empresaId,
                    'external_user_id' => $data['user_id'],
                    'marketplace' => 'mercadolivre',
                ],
                [
                    'nome_conta' => 'Mercado Livre - '.($data['user_id']),
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_at' => now()->addSeconds($data['expires_in']),
                    'ativo' => true,
                ]
            );

            // Dispara sincronização inicial em background
            SyncMeliAnunciosJob::dispatch($integracao);

            return redirect()->route('integrations.index')->with('message', 'Conta do Mercado Livre conectada com sucesso! ⚡');

        } catch (\Exception $e) {
            Log::error('Erro OAuth ML: '.$e->getMessage());

            return redirect()->route('integrations.index')->with('error', 'Erro ao processar conexão: '.$e->getMessage());
        }
    }

    public function updateNome(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:100',
        ]);

        $empresaId = Auth::user()->current_empresa_id;
        $meliService = new \App\Services\MeliIntegrationService($empresaId);

        if (! $meliService->isConnected()) {
            return redirect()->route('integrations.index')->with('error', 'Mercado Livre não conectado.');
        }

        if ($meliService->updateNome($request->nome)) {
            return redirect()->route('integrations.index')->with('message', 'Nome atualizado com sucesso!');
        }

        return redirect()->route('integrations.index')->with('error', 'Erro ao atualizar nome.');
    }

    public function testConnection()
    {
        $empresaId = session('empresa_id', 6);
        $meliService = new \App\Services\MeliIntegrationService($empresaId);

        if (! $meliService->isConnected()) {
            return response()->json(['success' => false, 'message' => 'Mercado Livre não conectado.']);
        }

        try {
            return response()->json([
                'success' => true,
                'message' => 'Conexão OK!',
                'data' => ['status' => 'connected'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro: '.$e->getMessage()]);
        }
    }
}
