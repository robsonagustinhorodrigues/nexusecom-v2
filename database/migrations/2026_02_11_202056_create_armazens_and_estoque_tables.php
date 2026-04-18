<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armazens', function (Blueprint $col) {
            $col->id();
            $col->string('nome');
            $col->string('slug')->unique();
            $col->text('endereco')->nullable();
            $col->boolean('compartilhado')->default(false);
            $col->boolean('ativo')->default(true);
            $col->timestamps();
        });

        Schema::create('armazem_empresa', function (Blueprint $col) {
            $col->id();
            $col->foreignId('armazem_id')->constrained('armazens')->onDelete('cascade');
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->timestamps();
        });

        Schema::create('estoque_movimentacoes', function (Blueprint $col) {
            $col->id();
            $col->foreignId('product_sku_id')->constrained('product_skus')->onDelete('cascade');
            $col->foreignId('armazem_id')->constrained('armazens')->onDelete('cascade');
            $col->foreignId('user_id')->nullable()->constrained();
            $col->integer('quantidade');
            $col->enum('tipo', ['entrada', 'saida', 'ajuste', 'reserva']);
            $col->string('origem')->nullable();
            $col->text('observacao')->nullable();
            $col->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_movimentacoes');
        Schema::dropIfExists('armazem_empresa');
        Schema::dropIfExists('armazens');
    }
};
