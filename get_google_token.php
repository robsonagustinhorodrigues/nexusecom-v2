<?php
require 'vendor/autoload.php';

$env = [];
$envFile = __DIR__ . '/.env';

if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        $env[$key] = $value;
    }
}

$clientId = $env['GOOGLE_CLIENT_ID']
    ?? $env['GOOGLE_DRIVE_CLIENT_ID']
    ?? null;

$clientSecret = $env['GOOGLE_CLIENT_SECRET']
    ?? $env['GOOGLE_DRIVE_CLIENT_SECRET']
    ?? null;

if (!$clientId || !$clientSecret) {
    fwrite(STDERR, "Configure GOOGLE_CLIENT_ID/GOOGLE_CLIENT_SECRET ou GOOGLE_DRIVE_CLIENT_ID/GOOGLE_DRIVE_CLIENT_SECRET no .env antes de executar este script.\n");
    exit(1);
}

$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
$client->addScope('https://www.googleapis.com/auth/drive.file');
$client->setAccessType('offline');
$client->setApprovalPrompt('force');

$authUrl = $client->createAuthUrl();

echo "1. Abra esta URL no seu navegador:\n$authUrl\n\n";
echo "2. Após autorizar, cole o código que o Google vai te dar aqui:\n";

$code = trim(fgets(STDIN));

if ($code === '') {
    fwrite(STDERR, "Nenhum código foi informado.\n");
    exit(1);
}

$token = $client->fetchAccessTokenWithAuthCode($code);

if (isset($token['error'])) {
    fwrite(STDERR, "Erro ao obter token: " . ($token['error_description'] ?? $token['error']) . "\n");
    exit(1);
}

if (empty($token['refresh_token'])) {
    fwrite(STDERR, "Google não retornou refresh_token. Tente revogar o app e autorizar novamente com prompt de consentimento.\n");
    exit(1);
}

echo "\nAqui está o seu REFRESH_TOKEN:\n";
echo $token['refresh_token'] . "\n";
echo "\nCopie esse valor e adicione ao seu arquivo .env no campo GOOGLE_DRIVE_REFRESH_TOKEN\n";
