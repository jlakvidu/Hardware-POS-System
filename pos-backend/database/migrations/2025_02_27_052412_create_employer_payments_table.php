<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')->constrained('cashier')->onDelete('cascade');
            $table->decimal('salary_amount', 10, 2);
            $table->enum('payment_duration', ['Daily', 'Weekly', 'Monthly', 'Other']);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_payments');
    }
};
