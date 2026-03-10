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
        Schema::table('nfe_emitidas', function (Blueprint $table) {
            if (!Schema::hasColumn('nfe_emitidas', 'tipo_fiscal')) {
                $table->enum('tipo_fiscal', ['entrada', 'saida'])->default('saida')->after('serie');
            }
            if (!Schema::hasColumn('nfe_emitidas', 'tp_nf')) {
                $table->integer('tp_nf')->nullable()->after('tipo_fiscal');
            }
            if (!Schema::hasColumn('nfe_emitidas', 'emitente_cnpj')) {
                $table->string('emitente_cnpj', 14)->nullable()->after('serie');
            }
            if (!Schema::hasColumn('nfe_emitidas', 'emitente_nome')) {
                $table->string('emitente_nome')->nullable()->after('emitente_cnpj');
            }
            // Add profit/financial fields if missing (based on previous research of related tasks)
            if (!Schema::hasColumn('nfe_emitidas', 'total_tributos')) {
                $table->decimal('total_tributos', 15, 2)->default(0)->after('valor_total');
            }
        });

        Schema::table('nfe_recebidas', function (Blueprint $table) {
            if (!Schema::hasColumn('nfe_recebidas', 'tipo_fiscal')) {
                $table->enum('tipo_fiscal', ['entrada', 'saida'])->default('entrada')->after('serie');
            }
            if (!Schema::hasColumn('nfe_recebidas', 'tp_nf')) {
                $table->integer('tp_nf')->nullable()->after('tipo_fiscal');
            }
            if (!Schema::hasColumn('nfe_recebidas', 'cliente_cnpj')) {
                $table->string('cliente_cnpj', 14)->nullable()->after('emitente_cnpj');
            }
            if (!Schema::hasColumn('nfe_recebidas', 'cliente_nome')) {
                $table->string('cliente_nome')->nullable()->after('cliente_cnpj');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->dropColumn(['tipo_fiscal', 'tp_nf', 'emitente_cnpj', 'emitente_nome', 'total_tributos']);
        });

        Schema::table('nfe_recebidas', function (Blueprint $table) {
            $table->dropColumn(['tipo_fiscal', 'tp_nf', 'cliente_cnpj', 'cliente_nome']);
        });
    }
};
