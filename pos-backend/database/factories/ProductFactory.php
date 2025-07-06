<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 1, 1000),
            'seller_price' => $this->faker->randomFloat(2, 1, 1000),
            'profit' => $this->faker->randomFloat(2, 0, 500),
            'discount' => $this->faker->randomFloat(2, 0, 100),
            'selling_discount' => $this->faker->randomFloat(2, 0, 100),
            'tax' => $this->faker->randomFloat(2, 0, 100),
            'size' => $this->faker->word,
            'color' => $this->faker->word,
            'description' => $this->faker->sentence,
            'bar_code' => $this->faker->unique()->ean13,
            'brand_name' => $this->faker->word,
            'inventory_id' => \App\Models\Inventory::factory(),
            'supplier_id' => \App\Models\Supplier::factory(),
            'admin_id' => \App\Models\Admin::factory(),
            'calculate_length' => $this->faker->boolean,
        ];
    }
}