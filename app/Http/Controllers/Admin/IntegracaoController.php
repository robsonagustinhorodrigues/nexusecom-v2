<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class IntegracaoController extends Controller
{
    /**
     * Upload de cookies do Mercado Livre para scraping
     */
    public function uploadCookies(Request $request)
    {
        try {
            $cookies = $request->input('cookies');
            $empresaId = $request->input('empresa_id');
            
            if (!$cookies || !is_array($cookies)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cookies inválidos'
                ], 400);
            }

            // Salva os cookies em arquivo
            $filename = 'cookies_meli_' . ($empresaId ?? 'default') . '.json';
            $path = storage_path('app/cookies/' . $filename);
            
            // Cria diretório se não existir
            if (!File::exists(storage_path('app/cookies'))) {
                File::makeDirectory(storage_path('app/cookies'), 0755, true);
            }
            
            File::put($path, json_encode($cookies, JSON_PRETTY_PRINT));

            // Log da atualização
            Log::info('Cookies do Mercado Livre atualizados', [
                'empresa_id' => $empresaId,
                'filename' => $filename,
                'cookies_count' => count($cookies)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cookies salvos com sucesso',
                'path' => $path
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar cookies: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar cookies: ' . $e->getMessage()
            ], 500);
        }
    }
}
