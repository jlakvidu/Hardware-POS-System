<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'discount' => $this->faker->randomFloat(2, 0, 100),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
        ];
    }
}