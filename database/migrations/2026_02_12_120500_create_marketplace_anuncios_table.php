<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_anuncios', function (Blueprint $col) {
            $col->id();
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->foreignId('integracao_id')->constrained('integracoes')->onDelete('cascade');
            $col->foreignId('product_sku_id')->nullable()->constrained('product_skus')->onDelete('set null');
            
            $col->string('marketplace')->default('mercadolivre');
            $col->string('external_id')->index(); // Item ID no ML (ex: MLB123456)
            $col->string('titulo');
            $col->decimal('preco', 12, 2)->nullable();
            $col->integer('estoque')->default(0);
            $col->string('status')->nullable(); // active, paused, closed
            $col->string('url_anuncio')->nullable();
            $col->json('json_data')->nullable(); // Resposta completa da API
            
            $col->timestamps();
            
            $col->unique(['integracao_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_anuncios');
    }
};
