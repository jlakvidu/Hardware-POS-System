<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierPaymentTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->decimal('amount_paid', 15, 2);
            $table->enum('payment_method', ['cash', 'check']);
            $table->string('check_number', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('supplier_payment_groups')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_payment_transactions');
    }
}
