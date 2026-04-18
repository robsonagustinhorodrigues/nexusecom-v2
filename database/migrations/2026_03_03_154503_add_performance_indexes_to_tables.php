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
            $table->index('empresa_id', 'idx_nfe_empresa');
            $table->index('status_nfe', 'idx_nfe_status');
            $table->index('pedido_marketplace', 'idx_nfe_pedido_mkt');
        });

        Schema::table('marketplace_pedidos', function (Blueprint $table) {
            $table->index('empresa_id', 'idx_pedidos_empresa');
            $table->index('status', 'idx_pedidos_status');
            $table->index('data_compra', 'idx_pedidos_data');
            $table->index('pedido_id', 'idx_pedidos_pedido_id');
        });

        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->index('empresa_id', 'idx_anuncios_empresa');
            $table->index('status', 'idx_anuncios_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->dropIndex('idx_anuncios_status');
            $table->dropIndex('idx_anuncios_empresa');
        });

        Schema::table('marketplace_pedidos', function (Blueprint $table) {
            $table->dropIndex('idx_pedidos_pedido_id');
            $table->dropIndex('idx_pedidos_data');
            $table->dropIndex('idx_pedidos_status');
            $table->dropIndex('idx_pedidos_empresa');
        });

        Schema::table('nfe_emitidas', function (Blueprint $table) {
            $table->dropIndex('idx_nfe_pedido_mkt');
            $table->dropIndex('idx_nfe_status');
            $table->dropIndex('idx_nfe_empresa');
        });
    }
};
