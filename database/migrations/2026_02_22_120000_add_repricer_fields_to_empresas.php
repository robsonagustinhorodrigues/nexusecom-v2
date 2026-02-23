<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('repricer_enabled')->default(false)->after('sefaz_ativo');
            $table->integer('repricer_intervalo_horas')->default(3)->after('repricer_enabled');
            $table->timestamp('repricer_ultima_execucao')->nullable()->after('repricer_intervalo_horas');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['repricer_enabled', 'repricer_intervalo_horas', 'repricer_ultima_execucao']);
        });
    }
};
