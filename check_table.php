<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $tables = DB::select("SELECT tablename FROM pg_tables WHERE tablename = 'cache'");
    if (count($tables) > 0) {
        echo "Table 'cache' exists.";
    } else {
        echo "Table 'cache' does NOT exist.";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
