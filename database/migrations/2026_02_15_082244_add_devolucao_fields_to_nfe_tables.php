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
        Schema::table('nfe_recebidas', function (Blueprint $table) {
            $table->boolean('devolucao')->default(false)->after('status_nfe');
            $table->string('nfe_devolvida_chave', 44)->nullable()->after('devolucao');
            $table->string('nfe_devolvida_numero', 9)->nullable()->after('nfe_devolvida_chave');
            $table->string('nfe_devolvida_serie', 3)->nullable()->after('nfe_devolvida_numero');
        });

        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->boolean('devolvida')->default(false)->after('status_nfe');
            $table->string('nfe_devolucao_chave', 44)->nullable()->after('devolvida');
            $table->string('nfe_devolucao_numero', 9)->nullable()->after('nfe_devolucao_chave');
            $table->string('nfe_devolucao_serie', 3)->nullable()->after('nfe_devolucao_numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfe_recebidas', function (Blueprint $table) {
            $table->dropColumn([
                'devolucao',
                'nfe_devolvida_chave',
                'nfe_devolvida_numero',
                'nfe_devolvida_serie',
            ]);
        });

        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->dropColumn([
                'devolvida',
                'nfe_devolucao_chave',
                'nfe_devolucao_numero',
                'nfe_devolucao_serie',
            ]);
        });
    }
};
