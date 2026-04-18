<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeliController extends Controller
{
    public function redirect()
    {
        $clientId = env('MELI_CLIENT_ID');
        $redirectUri = urlencode(env('MELI_REDIRECT_AUTH_URL'));

        $url = "https://auth.mercadolivre.com.br/authorization?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}";

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');

        if (! $code) {
            return redirect()->route('dashboard')->with('error', 'Código de autorização não recebido.');
        }

        try {
            $response = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => env('MELI_CLIENT_ID'),
                'client_secret' => env('MELI_CLIENT_SECRET'),
                'code' => $code,
                'redirect_uri' => env('MELI_REDIRECT_AUTH_URL'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Futuramente salvaremos isso na tabela 'integracoes' vinculada à empresa ativa
                Log::info('Meli Auth Success', $data);

                return redirect()->route('dashboard')->with('success', 'Conta Mercado Livre conectada com sucesso!');
            }

            return redirect()->route('dashboard')->with('error', 'Erro ao obter token do Mercado Livre.');

        } catch (\Exception $e) {
            Log::error('Meli Auth Error: '.$e->getMessage());

            return redirect()->route('dashboard')->with('error', 'Falha na comunicação com o Mercado Livre.');
        }
    }
}
