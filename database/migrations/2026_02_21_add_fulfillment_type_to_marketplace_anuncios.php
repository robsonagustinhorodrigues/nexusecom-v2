<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->enum('fulfillment_type', ['DBA', 'FBA'])->nullable()->after('sku');
            $table->string('fulfillment_sku')->nullable()->after('fulfillment_type');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_type', 'fulfillment_sku']);
        });
    }
};
