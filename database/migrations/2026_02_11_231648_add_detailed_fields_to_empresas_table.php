<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $col) {
            $col->string('razao_social')->nullable()->after('nome');
            $col->string('apelido')->nullable()->after('razao_social');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $col) {
            $col->dropColumn(['razao_social', 'apelido']);
        });
    }
};
