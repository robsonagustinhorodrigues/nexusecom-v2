<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estoque_saldos', function (Blueprint $col) {
            $col->id();
            $col->foreignId('product_sku_id')->constrained('product_skus')->onDelete('cascade');
            $col->foreignId('armazem_id')->constrained('armazens')->onDelete('cascade');
            $col->integer('saldo')->default(0);
            $col->timestamps();
            
            $col->unique(['product_sku_id', 'armazem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_saldos');
    }
};
