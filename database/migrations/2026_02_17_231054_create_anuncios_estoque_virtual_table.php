<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anuncios_estoque_virtual', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anuncio_id')->constrained('marketplace_anuncios')->onDelete('cascade');
            $table->integer('quantidade_virtual')->default(0);
            $table->boolean('usar_virtual')->default(false);
            $table->timestamps();

            $table->unique('anuncio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncios_estoque_virtual');
    }
};
