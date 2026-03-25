<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * Gera sugestões de marketing (KW, Categorias, ASINs) para um anúncio da Amazon.
     */
    public function generateAdsSuggestions(string $title, ?string $description = null, string $model = 'gemini-flash-latest'): array
    {
        // Enforce the use of gemini-flash-latest if gemini-1.5-flash is passed
        if ($model === 'gemini-1.5-flash') {
            $model = 'gemini-flash-latest';
        }

        $prompt = "Você é um especialista em Amazon Ads (Seller Pro). 
        Analise o seguinte anúncio da Amazon Brasil:
        Título: {$title}
        Descrição: " . ($description ?? 'N/A') . "
        
        Gere uma lista estruturada em JSON com:
        1. 'keywords': Uma lista de 10 palavras-chave relevantes (Broad e Exact mix).
        2. 'categories': Uma lista de 3 a 5 IDs de categorias ou nomes de categorias relevantes na Amazon Brasil.
        3. 'asins': Uma lista de 5 ASINs concorrentes genéricos que venderiam este mesmo tipo de produto.
        
        Responda APENAS o JSON puro, sem markdown, sem explicações.";

        try {
            $result = Gemini::generativeModel($model)->generateContent($prompt);
            $text = $result->text();

            // Limpa possíveis marcações de markdown
            $text = trim(str_replace(['```json', '```'], '', $text));
            $suggestions = json_decode($text, true);

            if (!$suggestions) {
                Log::error("Gemini Response is not valid JSON: " . $text);
                return [
                    'success' => false,
                    'message' => 'Erro ao processar resposta da IA.'
                ];
            }

            return [
                'success' => true,
                'data' => $suggestions
            ];

        } catch (\Exception $e) {
            Log::error('Exceção Gemini Service: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao comunicar com a IA do Google: ' . $e->getMessage()
            ];
        }
    }
}
