<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('inventory_id')->constrained('inventories');
            $table->foreignId('admin_id')->constrained('admins');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'check']);
            $table->string('check_number')->nullable();
            $table->timestamp('paid_at');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_payments');
    }
}
