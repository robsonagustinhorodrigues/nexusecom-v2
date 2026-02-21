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
            // Campos financeiros
            $table->decimal('valor_frete', 10, 2)->nullable()->change();
            $table->decimal('valor_taxa_platform', 10, 2)->nullable()->after('valor_frete')->comment('Taxa do marketplace');
            $table->decimal('valor_taxa_fixa', 10, 2)->nullable()->after('valor_taxa_platform')->comment('Taxa fixa por venda');
            $table->decimal('valor_taxa_pagamento', 10, 2)->nullable()->after('valor_taxa_fixa')->comment('Taxa de pagamento');
            $table->decimal('valor_imposto', 10, 2)->nullable()->after('valor_taxa_pagamento')->comment('Imposto calculado');
            $table->decimal('valor_outros', 10, 2)->nullable()->after('valor_imposto')->comment('Outras taxas');
            $table->decimal('valor_liquido', 10, 2)->nullable()->after('valor_outros')->comment('Valor líquido (recebido)');

            // Campos de controle de importação
            $table->timestamp('imported_at')->nullable()->after('json_data')->comment('Data da importação');
            $table->string('import_hash', 64)->nullable()->unique()->after('imported_at')->comment('Hash para evitar duplicatas');
            $table->boolean('import_confirmed')->default(false)->after('import_hash')->comment('Confirmação de importação');
            $table->text('import_error')->nullable()->after('import_confirmed')->comment('Erro na importação se houver');
            $table->timestamp('last_status_update')->nullable()->after('import_error')->comment('Última atualização de status');
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
