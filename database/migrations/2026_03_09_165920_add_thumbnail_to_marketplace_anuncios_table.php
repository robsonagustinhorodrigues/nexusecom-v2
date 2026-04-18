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
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->after('titulo');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            $table->dropColumn('thumbnail');
        });
    }
};
