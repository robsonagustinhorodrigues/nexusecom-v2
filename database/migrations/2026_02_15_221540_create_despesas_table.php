<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->date('data_pagamento');
            $table->date('data_competencia')->nullable();
            $table->enum('categoria', [
                'frete',
                'taxa_plataforma',
                'taxa_pagamento',
                'imposto',
                'marketing',
                'fornecedor',
                'funcionario',
                'aluguel',
                'luz',
                'agua',
                'internet',
                'telefone',
                'software',
                'marketing_digital',
                'embalagem',
                'estoque',
                'contabilidade',
                'juridico',
                'banco',
                'outros',
            ])->default('outros');
            $table->enum('status', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->enum('forma_pagamento', ['dinheiro', 'pix', 'transferencia', 'boleto', 'cartao_credito', 'cartao_debito', 'cheque'])->nullable();
            $table->boolean('recorrente')->default(false);
            $table->integer('recorrencia_meses')->nullable()->comment('Mesada, trimestral, etc');
            $table->foreignId('fornecedor_id')->nullable()->constrained('parceiros')->onDelete('set null');
            $table->foreignId('marketplace_pedido_id')->nullable()->constrained('marketplace_pedidos')->onDelete('set null');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'data_pagamento']);
            $table->index(['empresa_id', 'categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
