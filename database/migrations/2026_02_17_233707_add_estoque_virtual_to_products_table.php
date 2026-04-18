<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('quantidade_virtual')->default(0)->after('preco_venda');
            $table->boolean('usar_virtual')->default(false)->after('quantidade_virtual');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['quantidade_virtual', 'usar_virtual']);
        });
    }
};
