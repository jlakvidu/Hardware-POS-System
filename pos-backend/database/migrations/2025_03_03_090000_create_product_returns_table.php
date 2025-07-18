<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductReturnsTable extends Migration
{
    public function up()
    {
        Schema::create('product_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->string('returned_by')->nullable();
            $table->timestamp('return_date')->useCurrent();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('inventory_id')->references('id')->on('inventories');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_returns');
    }
}
