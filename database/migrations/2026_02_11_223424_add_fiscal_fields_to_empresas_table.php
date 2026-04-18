<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $col) {
            $col->string('cnpj', 18)->nullable()->unique()->after('nome');
            $col->string('logo_path')->nullable()->after('cnpj');
            $col->string('certificado_a1_path')->nullable()->after('logo_path');
            $col->text('certificado_senha')->nullable()->after('certificado_a1_path'); // Criptografar depois
            $col->string('email_contabil')->nullable()->after('certificado_senha');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $col) {
            $col->dropColumn(['cnpj', 'logo_path', 'certificado_a1_path', 'certificado_senha', 'email_contabil']);
        });
    }
};
