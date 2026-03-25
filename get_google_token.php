<?php
require vendor/autoload.php;

$clientId = getenv(GOOGLE_CLIENT_ID) ?: ($_ENV[GOOGLE_CLIENT_ID] ?? null);
$clientSecret = getenv(GOOGLE_CLIENT_SECRET) ?: ($_ENV[GOOGLE_CLIENT_SECRET] ?? null);

if (!$clientId || !$clientSecret) {
    fwrite(STDERR, "Configure GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no ambiente antes de executar este script.\n");
    exit(1);
}

$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri(urn:ietf:wg:oauth:2.0:oob);
$client->addScope(https://www.googleapis.com/auth/drive.file);
$client->setAccessType(offline);
$client->setApprovalPrompt(force);

$authUrl = $client->createAuthUrl();

echo "1. Abra esta URL no seu navegador:\n$authUrl\n\n";
echo "2. Após autorizar, cole o código que o Google vai te dar aqui:\n";

$code = trim(fgets(STDIN));

$token = $client->fetchAccessTokenWithAuthCode($code);

echo "\nAqui está o seu REFRESH_TOKEN:\n";
echo $token[refresh_token] . "\n";
echo "\nCopie esse valor e adicione ao seu arquivo .env no campo GOOGLE_DRIVE_REFRESH_TOKEN\n";
