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
        Schema::table('marketplace_pedidos', function (Blueprint $query) {
            $query->json('order_json')->nullable()->after('json_data');
            $query->json('cart_json')->nullable()->after('order_json');
            $query->json('payments_json')->nullable()->after('cart_json');
            $query->json('shipments_json')->nullable()->after('payments_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_pedidos', function (Blueprint $query) {
            $query->dropColumn(['order_json', 'cart_json', 'payments_json', 'shipments_json']);
        });
    }
};
