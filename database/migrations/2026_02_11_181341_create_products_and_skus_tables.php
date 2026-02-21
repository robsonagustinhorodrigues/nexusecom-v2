<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $col) {
            $col->id();
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->string('nome');
            $col->string('slug')->unique();
            $col->string('marca')->nullable();
            $col->text('descricao')->nullable();
            $col->enum('tipo', ['simples', 'variacao', 'composto'])->default('simples');
            $col->timestamps();
        });

        Schema::create('product_skus', function (Blueprint $col) {
            $col->id();
            $col->foreignId('product_id')->constrained()->onDelete('cascade');
            $col->string('sku')->unique();
            $col->string('gtin')->nullable();
            $col->string('label')->nullable(); // Nome da variação (ex: Azul / G)
            $col->decimal('preco_venda', 12, 2)->default(0);
            $col->decimal('preco_custo', 12, 2)->default(0);
            $col->integer('estoque')->default(0);
            $col->integer('peso_g')->nullable();
            $col->integer('comprimento_cm')->nullable();
            $col->integer('largura_cm')->nullable();
            $col->integer('altura_cm')->nullable();
            $col->text('descricao_sku')->nullable(); // Caso queira mudar a descrição só desse SKU
            $col->json('fotos_sku')->nullable();
            $col->json('atributos_json')->nullable(); // Armazena {Cor: Azul, Tamanho: G}
            $col->string('link_fornecedor')->nullable();
            $col->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_skus');
        Schema::dropIfExists('products');
    }
};
