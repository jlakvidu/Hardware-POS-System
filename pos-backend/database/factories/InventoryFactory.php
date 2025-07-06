<?php

namespace Database\Factories;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition()
    {
        return [
            'quantity' => $this->faker->randomFloat(2, 0, 1000),
            'restock_date_time' => $this->faker->dateTime(),
            'added_stock_amount' => $this->faker->numberBetween(0, 100),
            'location' => $this->faker->word,
            'status' => $this->faker->randomElement(['In Stock', 'Low Stock', 'Out Of Stock']),
        ];
    }
}