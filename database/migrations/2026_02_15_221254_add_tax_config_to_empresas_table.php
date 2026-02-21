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
        Schema::table('empresas', function (Blueprint $table) {
            // Regime Tributário
            $table->enum('regime_tributario', ['simples_nacional', 'lucro_presumido', 'lucro_real'])->default('simples_nacional')->after('tipo_atividade');
            $table->decimal('aliquota_icms', 5, 2)->default(0)->after('regime_tributario')->comment('Alíquota ICMS %');
            $table->decimal('aliquota_pis', 5, 2)->default(0)->after('aliquota_icms')->comment('Alíquota PIS %');
            $table->decimal('aliquota_cofins', 5, 2)->default(0)->after('aliquota_pis')->comment('Alíquota COFINS %');
            $table->decimal('aliquota_csll', 5, 2)->default(0)->after('aliquota_cofins')->comment('Alíquota CSLL %');
            $table->decimal('aliquota_irpj', 5, 2)->default(0)->after('aliquota_csll')->comment('Alíquota IRPJ %');
            $table->decimal('aliquota_iss', 5, 2)->default(0)->after('aliquota_irpj')->comment('Alíquota ISS %');
            $table->decimal('percentual_lucro_presumido', 5, 2)->default(32)->after('aliquota_iss')->comment('% Lucro Presumido para IRPJ/CSLL');

            // Configurações adicionais
            $table->boolean('calcula_imposto_auto')->default(true)->after('percentual_lucro_presumido')->comment('Calcular imposto automaticamente nos pedidos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            //
        });
    }
};
