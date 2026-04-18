<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parceiros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('nome');
            $table->string('cpf_cnpj', 18)->nullable();
            $table->enum('tipo', ['cliente', 'fornecedor', 'ambos'])->default('cliente');
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->text('endereco')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            
            $table->unique(['empresa_id', 'cpf_cnpj']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parceiros');
    }
};
