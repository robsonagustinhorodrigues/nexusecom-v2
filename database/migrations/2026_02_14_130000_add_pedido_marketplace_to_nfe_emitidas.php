<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->string('pedido_marketplace', 100)->nullable()->after('chave');
        });
    }

    public function down(): void
    {
        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->dropColumn('pedido_marketplace');
        });
    }
};
