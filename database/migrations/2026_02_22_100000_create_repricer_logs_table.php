<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repricer_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_anuncio_id')->constrained('marketplace_anuncios')->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            
            // Dados da execução
            $table->string('strategy')->nullable();
            $table->decimal('preco_anterior', 12, 2)->nullable();
            $table->decimal('preco_novo', 12, 2)->nullable();
            $table->decimal('menor_concorrente', 12, 2)->nullable();
            $table->decimal('margem_lucro', 5, 2)->nullable();
            $table->decimal('lucro_bruto', 12, 2)->nullable();
            
            // Status e mensagem
            $table->string('status')->default('success'); // success, skipped, error
            $table->text('mensagem')->nullable();
            $table->json('detalhes')->nullable(); // Dados extras em JSON
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repricer_logs');
    }
};
