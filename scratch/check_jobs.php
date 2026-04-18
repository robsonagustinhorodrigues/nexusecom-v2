<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Ultimas Tarefas:\n";
$tarefas = \DB::table('tarefas')->orderBy('id', 'desc')->limit(5)->get();
foreach ($tarefas as $t) {
    echo "ID: {$t->id}, Tipo: {$t->tipo}, Status: {$t->status}, Msg: {$t->mensagem}\n";
}

echo "\nFailed Jobs:\n";
$failed = \DB::table('failed_jobs')->orderBy('id', 'desc')->limit(2)->get();
foreach ($failed as $f) {
    echo "ID: {$f->id}, Exception: " . substr($f->exception, 0, 200) . "\n";
}
