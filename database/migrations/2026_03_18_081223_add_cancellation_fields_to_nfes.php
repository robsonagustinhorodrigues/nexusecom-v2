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
            $table->string('protocolo_cancelamento')->nullable();
            $table->text('motivo_cancelamento')->nullable();
        });

        Schema::table('nfe_recebidas', function (Blueprint $table) {
            $table->string('protocolo_cancelamento')->nullable();
            $table->text('motivo_cancelamento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->dropColumn(['protocolo_cancelamento', 'motivo_cancelamento']);
        });

        Schema::table('nfe_recebidas', function (Blueprint $table) {
            $table->dropColumn(['protocolo_cancelamento', 'motivo_cancelamento']);
        });
    }
};
