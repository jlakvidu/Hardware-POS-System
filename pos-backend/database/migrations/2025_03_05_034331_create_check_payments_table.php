<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->onDelete('cascade');
            $table->string('bank_name');
            $table->string('check_number');
            $table->date('check_date');
            $table->decimal('amount', 10, 2);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_payments');
    }
};
