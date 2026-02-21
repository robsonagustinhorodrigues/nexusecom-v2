<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('custo_adicional', 10, 2)->default(0)->after('preco_custo');
            $table->string('unidade_custo_adicional')->default('unidade')->after('custo_adicional');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['custo_adicional', 'unidade_custo_adicional']);
        });
    }
};
