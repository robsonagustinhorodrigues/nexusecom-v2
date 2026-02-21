<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->tinyInteger('sefaz_intervalo_horas')->default(6)->comment('Intervalo em horas entre consultas SEFAZ');
            $table->tinyInteger('tpAmb')->default(1)->comment('1=Produção, 2=Homologação');
            $table->boolean('sefaz_ativo')->default(true)->comment('Ativar consultas automáticas SEFAZ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            //
        });
    }
};
