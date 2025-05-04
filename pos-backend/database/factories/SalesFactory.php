<?php

namespace Database\Factories;

use App\Models\Sales;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesFactory extends Factory
{
    protected $model = Sales::class;

    public function definition()
    {
        return [
            'customer_id' => Customer::factory(),
            'cashier_id' => User::factory()->create(['role' => 'cashier'])->id,
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'payment_type' => $this->faker->randomElement(['CASH', 'CREDIT_CARD', 'DEBIT_CARD']),
            'status' => $this->faker->randomElement([0, 1]), // 0 for pending, 1 for completed
            'time' => $this->faker->dateTime(),
            'cart_discount' => $this->faker->randomFloat(2, 0, 100),
            'product_discounts_total' => $this->faker->randomFloat(2, 0, 100),
            'total_discount_amount' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}