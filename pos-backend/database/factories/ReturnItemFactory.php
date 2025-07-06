<?php

namespace Database\Factories;

use App\Models\ReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnItemFactory extends Factory
{
    protected $model = ReturnItem::class;

    public function definition()
    {
        return [
            'reason' => $this->faker->sentence,
            'quantity' => $this->faker->numberBetween(1, 100),
            'product_id' => $this->faker->randomNumber(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}