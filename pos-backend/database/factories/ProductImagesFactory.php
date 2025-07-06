<?php

namespace Database\Factories;

use App\Models\ProductImages;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImagesFactory extends Factory
{
    protected $model = ProductImages::class;

    public function definition()
    {
        return [
            'product_id' => $this->faker->numberBetween(1, 100),
            'path' => $this->faker->imageUrl(),
            'name' => $this->faker->word(),
            'size' => $this->faker->numberBetween(100, 5000),
        ];
    }
}