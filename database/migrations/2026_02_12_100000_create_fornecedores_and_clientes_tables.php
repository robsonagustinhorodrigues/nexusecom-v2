<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $col) {
            $col->id();
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->string('razao_social');
            $col->string('nome_fantasia')->nullable();
            $col->string('cnpj', 18)->nullable();
            $col->string('email')->nullable();
            $col->string('telefone', 20)->nullable();
            $col->text('endereco')->nullable();
            $col->boolean('ativo')->default(true);
            $col->timestamps();
        });

        Schema::create('clientes', function (Blueprint $col) {
            $col->id();
            $col->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $col->string('nome');
            $col->string('cpf_cnpj', 18)->nullable();
            $col->enum('tipo', ['pf', 'pj'])->default('pf');
            $col->string('email')->nullable();
            $col->string('telefone', 20)->nullable();
            $col->text('endereco')->nullable();
            $col->boolean('ativo')->default(true);
            $col->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('fornecedores');
    }
};
