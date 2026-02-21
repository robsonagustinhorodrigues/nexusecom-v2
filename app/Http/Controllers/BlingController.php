<?php

namespace App\Http\Controllers;

use App\Services\BlingIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlingController extends Controller
{
    public function index()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $blingService = new BlingIntegrationService($empresaId);

        return view('livewire.integrations.bling', [
            'bling' => $blingService->getIntegracao(),
            'isConnected' => $blingService->isConnected(),
        ]);
    }

    public function connect(Request $request)
    {
        $oauthUrl = config('services.bling.oauth_url', 'https://www.bling.com.br/Api/v3/oauth/authorize');
        $clientId = config('services.bling.client_id', env('BLING_CLIENT_ID'));
        $redirectUri = config('services.bling.redirect_uri', env('BLING_REDIRECT_URI'));

        $state = bin2hex(random_bytes(16));
        session(['bling_oauth_state' => $state]);

        if ($request->has('nome_conta')) {
            session(['bling_nome_conta' => $request->nome_conta]);
        }

        $url = $oauthUrl.'?response_type=code&client_id='.$clientId.'&redirect_uri='.urlencode($redirectUri).'&state='.$state;

        return redirect($url);
    }

    public function oauthCallback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');

        if (! $code) {
            return redirect()->route('integrations.index')->with('error', 'Código de autorização não recebido.');
        }

        if ($state !== session('bling_oauth_state')) {
            return redirect()->route('integrations.index')->with('error', 'Estado CSRF inválido.');
        }

        try {
            $clientId = config('services.bling.client_id');
            $clientSecret = config('services.bling.client_secret');
            $redirectUri = config('services.bling.redirect_uri');

            $response = Http::withHeaders([
                'Authorization' => 'Basic '.base64_encode($clientId.':'.$clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('https://www.bling.com.br/Api/v3/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

            Log::info('Bling OAuth response status: '.$response->status());
            Log::info('Bling OAuth response body: '.$response->body());

            if ($response->successful()) {
                $data = $response->json();

                $empresaId = Auth::user()->current_empresa_id;
                $blingService = new BlingIntegrationService($empresaId);

                $nomeConta = session('bling_nome_conta', 'Bling ERP');
                session()->forget('bling_nome_conta');

                $blingService->saveOAuthTokens(
                    $data['access_token'],
                    $data['refresh_token'],
                    $data['expires_in'],
                    $nomeConta
                );

                return redirect()->route('integrations.index')->with('message', 'Bling conectado com sucesso! ⚡');
            }

            Log::error('Bling OAuth error: '.$response->body());

            return redirect()->route('integrations.index')->with('error', 'Falha ao obter token de acesso: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Bling OAuth exception: '.$e->getMessage());

            return redirect()->route('integrations.index')->with('error', 'Erro na autenticação: '.$e->getMessage());
        }
    }

    public function notificationCallback(Request $request)
    {
        $data = $request->all();

        Log::info('Bling notification received: '.json_encode($data));

        $tipo = $data['tipo'] ?? null;
        $id = $data['id'] ?? null;

        if (! $tipo || ! $id) {
            return response()->json(['success' => false, 'message' => 'Dados inválidos'], 400);
        }

        try {
            $integracoes = \App\Models\Integracao::where('marketplace', 'bling')
                ->where('ativo', true)
                ->get();

            foreach ($integracoes as $integracao) {
                $blingService = new BlingIntegrationService($integracao->empresa_id);
                $blingService->processarNotificacao($tipo, $id);

                Log::info("Notificação {$tipo} ID {$id} processada para empresa {$integracao->empresa_id}");
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar notificação do Bling: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function disconnect()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $blingService = new BlingIntegrationService($empresaId);

        $blingService->disconnect();

        return redirect()->route('integrations.index')->with('message', 'Bling desconectado com sucesso.');
    }

    public function updateNome(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:100',
        ]);

        $empresaId = Auth::user()->current_empresa_id;
        $blingService = new BlingIntegrationService($empresaId);

        if (! $blingService->isConnected()) {
            return redirect()->route('integrations.index')->with('error', 'Bling não está conectado.');
        }

        if ($blingService->updateNome($request->nome)) {
            return redirect()->route('integrations.index')->with('message', 'Nome atualizado com sucesso!');
        }

        return redirect()->route('integrations.index')->with('error', 'Erro ao atualizar nome.');
    }

    public function syncProdutos()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $blingService = new BlingIntegrationService($empresaId);

        if (! $blingService->isConnected()) {
            return redirect()->route('integrations.index')->with('error', 'Bling não está conectado.');
        }

        $resultado = $blingService->syncProdutosAsAnuncios();

        $mensagem = "Sincronizado: {$resultado['importados']} importados, {$resultado['atualizados']} atualizados";

        if ($resultado['erros'] > 0) {
            $mensagem .= ", {$resultado['erros']} erros";
        }

        return redirect()->route('integrations.index')->with('message', $mensagem);
    }

    public function syncPedidos()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $blingService = new BlingIntegrationService($empresaId);

        if (! $blingService->isConnected()) {
            return redirect()->route('integrations.index')->with('error', 'Bling não está conectado.');
        }

        $pedidos = $blingService->getPedidos();

        return redirect()->route('integrations.index')->with('message', 'Pedidos sincronizados! Total: '.($pedidos['retorno']['pedidos'] ?? 0));
    }

    public function processarNotificacoes()
    {
        $empresaId = Auth::user()->current_empresa_id;
        $blingService = new BlingIntegrationService($empresaId);

        if (! $blingService->isConnected()) {
            return redirect()->route('integrations.index')->with('error', 'Bling não está conectado.');
        }

        $resultado = $blingService->processarNotificacoes();

        $mensagem = "Notificações processadas: {$resultado['pedidos']} pedidos, {$resultado['notas']} notas fiscais.";

        if (! empty($resultado['erros'])) {
            $mensagem .= ' Erros: '.count($resultado['erros']);
        }

        return redirect()->route('integrations.index')->with('message', $mensagem);
    }
}
