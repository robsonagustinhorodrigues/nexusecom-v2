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
        Schema::table('amazon_ads_configs', function (Blueprint $table) {
            $table->string('gemini_model')->default('gemini-1.5-flash')->after('margem_alvo_padrao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('amazon_ads_configs', function (Blueprint $table) {
            $table->dropColumn('gemini_model');
        });
    }
};
