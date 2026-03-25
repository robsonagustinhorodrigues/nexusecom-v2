<?php
require __DIR__.'/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GOOGLE_AI_API_KEY'] ?? 'MISSING';
echo "Testing Key: " . substr($apiKey, 0, 4) . "..." . substr($apiKey, -4) . " (Length: " . strlen($apiKey) . ")\n";

$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true); // Use GET
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $status\n";
echo "Response: $response\n";
