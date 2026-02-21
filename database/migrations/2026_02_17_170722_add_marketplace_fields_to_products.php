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
            $table->string('marketplace')->nullable()->after('ean');
            $table->string('external_id')->nullable()->after('marketplace');
            $table->string('marketplace_url')->nullable()->after('external_id');
            $table->string('condicao')->nullable()->after('marketplace_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['marketplace', 'external_id', 'marketplace_url', 'condicao']);
        });
    }
};
