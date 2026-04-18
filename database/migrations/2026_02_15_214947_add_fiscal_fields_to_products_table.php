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
        Schema::table('products', function (Blueprint $table) {
            $table->string('unidade_medida', 10)->default('UN')->after('descricao');
            $table->string('ncm', 15)->nullable()->after('unidade_medida');
            $table->string('cest', 15)->nullable()->after('ncm');
            $table->string('origem', 2)->default('0')->after('cest');
            $table->decimal('preco_venda', 12, 2)->default(0)->after('origem');
            $table->decimal('preco_custo', 12, 2)->default(0)->after('preco_venda');
            $table->decimal('peso', 10, 3)->nullable()->after('preco_custo');
            $table->decimal('altura', 8, 2)->nullable()->after('peso');
            $table->decimal('largura', 8, 2)->nullable()->after('altura');
            $table->decimal('profundidade', 8, 2)->nullable()->after('largura');
            $table->string('imagem')->nullable()->after('profundidade');
            $table->boolean('ativo')->default(true)->after('imagem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
