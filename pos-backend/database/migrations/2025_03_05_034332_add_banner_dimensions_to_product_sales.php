<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->double('width_inch')->nullable();
            $table->double('height_inch')->nullable();
            $table->double('area_sqm')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropColumn(['width_inch', 'height_inch', 'area_sqm']);
        });
    }
};
