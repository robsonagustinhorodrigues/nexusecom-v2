<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $col) {
            $col->id();
            $col->string('nome');
            $col->string('slug')->unique();
            $col->boolean('ativo')->default(true);
            $col->timestamps();
        });

        Schema::create('empresa_user', function (Blueprint $col) {
            $col->id();
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->foreignId('user_id')->constrained()->onDelete('cascade');
            $col->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_user');
        Schema::dropIfExists('empresas');
    }
};
