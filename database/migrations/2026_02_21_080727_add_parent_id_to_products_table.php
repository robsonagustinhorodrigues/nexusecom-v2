<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->string('variation_color')->nullable()->after('parent_id');
            $table->string('variation_size')->nullable()->after('variation_color');
            $table->integer('variation_order')->default(0)->after('variation_size');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'variation_color', 'variation_size', 'variation_order']);
        });
    }
};
