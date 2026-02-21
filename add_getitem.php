<?php
// Add getItem method to MeliIntegrationService

$file = 'app/Services/MeliIntegrationService.php';
$content = file_get_contents($file);

// Add before the last closing brace
$method = '
    /**
     * Busca detalhes de um item do ML
     */
    public function getItem(string $itemId): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        try {
            $response = Http::withHeaders([
                \'Authorization\' => \'Bearer \'.$token,
            ])->get("https://api.mercadolibre.com/items/{$itemId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error(\'Meli getItem exception: \' . $e->getMessage());
            return null;
        }
    }
}';

// Find the last closing brace
$pos = strrpos($content, '}');
if ($pos !== false) {
    $content = substr($content, 0, $pos) . $method;
    file_put_contents($file, $content);
    echo "Done\n";
} else {
    echo "Could not find closing brace\n";
}
