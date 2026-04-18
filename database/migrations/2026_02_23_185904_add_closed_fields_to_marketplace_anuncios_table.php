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
            $table->timestamp('closed_at')->nullable()->after('status');
            $table->string('closed_reason')->nullable()->after('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_anuncios', function (Blueprint $table) {
            //
        });
    }
};
