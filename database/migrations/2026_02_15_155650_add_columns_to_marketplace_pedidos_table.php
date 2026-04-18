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
        Schema::table('marketplace_pedidos', function (Blueprint $table) {
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('integracao_id')->nullable()->constrained('integracoes')->nullOnDelete();
            $table->string('marketplace');
            $table->string('pedido_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('status')->nullable();
            $table->string('status_pagamento')->nullable();
            $table->string('status_envio')->nullable();
            $table->string('comprador_nome')->nullable();
            $table->string('comprador_email')->nullable();
            $table->string('comprador_cpf')->nullable();
            $table->string('comprador_cnpj')->nullable();
            $table->string('telefone')->nullable();
            $table->text('endereco')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();
            $table->string('cep')->nullable();
            $table->decimal('valor_total', 10, 2)->nullable();
            $table->decimal('valor_frete', 10, 2)->nullable();
            $table->decimal('valor_desconto', 10, 2)->nullable();
            $table->decimal('valor_produtos', 10, 2)->nullable();
            $table->timestamp('data_compra')->nullable();
            $table->timestamp('data_pagamento')->nullable();
            $table->timestamp('data_envio')->nullable();
            $table->timestamp('data_entrega')->nullable();
            $table->string('codigo_rastreamento')->nullable();
            $table->string('url_rastreamento')->nullable();
            $table->json('json_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_pedidos', function (Blueprint $table) {
            //
        });
    }
};
