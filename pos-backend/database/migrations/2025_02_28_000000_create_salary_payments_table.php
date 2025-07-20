<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalaryPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')->constrained('cashier')->onDelete('cascade');
            $table->string('payment_period');
            $table->date('payment_date');
            $table->decimal('base_salary', 12, 2);
            $table->decimal('additions', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2);
            $table->string('payment_method');
            $table->string('notes')->nullable();
            $table->string('invoice_number')->unique();
            $table->string('status')->default('Paid');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_payments');
    }
}
