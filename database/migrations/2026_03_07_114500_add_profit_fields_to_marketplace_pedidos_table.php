<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_pedidos', function (Blueprint $table) {
            $table->decimal('lucro', 13, 2)->nullable()->after('valor_liquido')->comment('Lucro líquido do pedido');
            $table->decimal('custo_total', 13, 2)->nullable()->after('lucro')->comment('Custo agregado dos itens');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_pedidos', function (Blueprint $table) {
            $table->dropColumn(['lucro', 'custo_total']);
        });
    }
};
