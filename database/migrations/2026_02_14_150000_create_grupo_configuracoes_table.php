<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->unique()->constrained()->onDelete('cascade');
            $table->integer('sefaz_intervalo_minutos')->default(360); // 6 horas
            $table->boolean('sefaz_auto_busca')->default(true);
            $table->time('sefaz_hora_inicio')->default('08:00:00');
            $table->time('sefaz_hora_fim')->default('20:00:00');
            $table->boolean('nfe_auto_manifestar')->default(false);
            $table->integer('nfe_dias_retroativos')->default(5);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_configuracoes');
    }
};
