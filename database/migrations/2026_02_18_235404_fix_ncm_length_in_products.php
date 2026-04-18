<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('ncm', 20)->nullable()->change();
            $table->string('cest', 20)->nullable()->change();
            $table->string('tipo', 20)->default('P')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('ncm', 2)->change();
            $table->string('cest', 2)->change();
            $table->string('tipo', 2)->change();
        });
    }
};
