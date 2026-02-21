<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = \App\Models\User::first();
if($u) {
    $u->password = \Illuminate\Support\Facades\Hash::make('password');
    $u->save();
    echo 'Email: ' . $u->email . ' | Senha: password';
} else {
    echo 'No users found';
}
