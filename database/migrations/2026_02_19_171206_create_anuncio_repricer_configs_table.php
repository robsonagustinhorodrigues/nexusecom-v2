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
        Schema::create('anuncio_repricer_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_anuncio_id')->constrained('marketplace_anuncios')->onDelete('cascade');
            $table->boolean('is_active')->default(false);
            
            // Estratégia de precificação
            $table->string('strategy')->default('igualar_menor'); // igualar_menor, valor_abaixo, valor_acima
            $table->decimal('offset_value', 12, 2)->default(0); // R$ ou % conforme for implementado (usaremos R$ fixo por enquanto)
            
            // Limites de Lucratividade (Margens)
            $table->decimal('min_profit_margin', 5, 2)->nullable(); // Ex: 10.00 (%)
            $table->decimal('max_profit_margin', 5, 2)->nullable(); // Ex: 30.00 (%)
            
            // Filtros de Competição
            $table->boolean('filter_full_only')->default(false);
            $table->boolean('filter_premium_only')->default(false);
            $table->boolean('filter_classic_only')->default(false);
            
            // Controle de Execução
            $table->timestamp('last_run_at')->nullable();
            $table->text('log_last_action')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anuncio_repricer_configs');
    }
};
