<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfe_recebidas', function (Blueprint $table) {
            $table->enum('status_nfe', ['aprovada', 'denegada', 'inutilizada', 'cancelada'])->nullable()->after('status_manifestacao')->default('aprovada');
        });

        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->enum('status_nfe', ['aprovada', 'denegada', 'inutilizada', 'cancelada'])->nullable()->after('status')->default('aprovada');
        });
    }

    public function down(): void
    {
        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->dropColumn('status_nfe');
        });

        Schema::table('nfe_recebidas', function (Blueprint $table) {
            $table->dropColumn('status_nfe');
        });
    }
};
