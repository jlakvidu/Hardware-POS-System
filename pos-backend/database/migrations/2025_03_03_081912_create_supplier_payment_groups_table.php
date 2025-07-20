<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierPaymentGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_payment_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('admin_id');
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('remaining_balance', 15, 2)->default(0);
            $table->enum('payment_status', ['advance', 'full'])->default('advance');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('inventory_id')->references('id')->on('inventories');
            $table->foreign('admin_id')->references('id')->on('admins');
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_payment_groups');
    }
}
