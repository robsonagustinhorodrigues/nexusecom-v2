<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estoque_saldos', function (Blueprint $table) {
            if (Schema::hasColumn('estoque_saldos', 'armazem_id') && !Schema::hasColumn('estoque_saldos', 'deposito_id')) {
                $table->renameColumn('armazem_id', 'deposito_id');
            }
        });

        Schema::table('estoque_saldos', function (Blueprint $table) {
            if (Schema::hasColumn('estoque_saldos', 'deposito_id')) {
                $table->foreign('deposito_id')->references('id')->on('depositos')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estoque_saldos', function (Blueprint $table) {
            $table->dropForeign(['deposito_id']);
            $table->renameColumn('deposito_id', 'armazem_id');
        });
    }
};
