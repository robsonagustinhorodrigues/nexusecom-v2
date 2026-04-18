<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->after('nome')->index();
            $table->bigInteger('bling_id')->nullable()->unique()->after('id');
            $table->integer('estoque')->default(0)->after('preco_custo');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sku', 'bling_id', 'estoque']);
        });
    }
};
