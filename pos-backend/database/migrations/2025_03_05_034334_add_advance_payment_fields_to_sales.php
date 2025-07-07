<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('advance_amount', 10, 2)->default(0)->change();
            $table->decimal('remaining_balance', 10, 2)->default(0)->change();
            $table->string('payment_status')->default('FULL_PAYMENT')->change();
            $table->boolean('status')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Reverse the changes if needed
            $table->decimal('advance_amount', 10, 2)->default(0)->change();
            $table->decimal('remaining_balance', 10, 2)->default(0)->change();
            $table->string('payment_status')->change();
            $table->boolean('status')->change();
        });
    }
};
