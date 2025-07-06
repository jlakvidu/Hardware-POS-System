<?php

namespace Database\Factories;

use App\Models\Product_Sales;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductSalesFactory extends Factory
{
    protected $model = Product_Sales::class;

    public function definition()
    {
        return [
            'product_id' => $this->faker->randomNumber(),
            'sales_id' => $this->faker->randomNumber(),
            'quantity' => $this->faker->randomDigitNotZero(),
            'price' => $this->faker->randomFloat(2, 1, 1000),
        ];
    }
}