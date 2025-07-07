<?php

use App\Models\Cashier;
use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->dateTime('time');
            $table->boolean('status');
            $table->enum('payment_type', ['CASH', 'CARD', 'CHECK', 'ONLINE']);
            $table->double('amount');
            $table->decimal('advance_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_balance', 10, 2)->default(0.00);
            $table->string('payment_status')->default('FULL_PAYMENT');
            $table->json('check_details')->nullable();
            $table->double('cart_discount')->default(0); // Renamed from discount to cart_discount
            $table->double('product_discounts_total')->default(0); // New column for sum of product discounts
            $table->double('total_discount_amount')->default(0); // New column for total discount amount
            $table->foreignIdFor(Cashier::class);
            $table->foreignIdFor(Customer::class)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
