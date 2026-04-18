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
        Schema::create('nfe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nfe_emitida_id')->nullable()->constrained('nfe_emitidas')->onDelete('cascade');
            $table->foreignId('nfe_recebida_id')->nullable()->constrained('nfe_recebidas')->onDelete('cascade');
            $table->integer('numero_item')->unsigned();
            $table->string('codigo_produto')->nullable();
            $table->string('gtin')->nullable();
            $table->string('descricao');
            $table->string('ncm')->nullable();
            $table->string('cfop')->nullable();
            $table->string('unidade')->nullable();
            $table->decimal('quantidade', 15, 4)->default(0);
            $table->decimal('valor_unitario', 15, 4)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_frete', 15, 2)->default(0);
            $table->decimal('valor_seguro', 15, 2)->default(0);
            $table->decimal('valor_outros', 15, 2)->default(0);
            $table->decimal('base_calculo_icms', 15, 2)->default(0);
            $table->decimal('aliquota_icms', 7, 4)->default(0);
            $table->decimal('valor_icms', 15, 2)->default(0);
            $table->decimal('base_calculo_icms_st', 15, 2)->default(0);
            $table->decimal('aliquota_icms_st', 7, 4)->default(0);
            $table->decimal('valor_icms_st', 15, 2)->default(0);
            $table->decimal('aliquota_pis', 7, 4)->default(0);
            $table->decimal('valor_pis', 15, 2)->default(0);
            $table->decimal('aliquota_cofins', 7, 4)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);
            $table->decimal('aliquota_iss', 7, 4)->default(0);
            $table->decimal('valor_iss', 15, 2)->default(0);
            $table->boolean('tributado')->default(true);
            $table->string('codigo_beneficio_fiscal')->nullable();
            $table->text('informacoes_adicionais')->nullable();
            $table->index(['nfe_emitida_id']);
            $table->index(['nfe_recebida_id']);
            $table->index(['ncm']);
            $table->index(['cfop']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfe_items');
    }
};
