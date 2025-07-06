<?php

namespace Database\Factories;

use App\Models\SupplierProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierProductFactory extends Factory
{
    protected $model = SupplierProduct::class;

    public function definition()
    {
        return [
            'supplier_id' => $this->faker->randomNumber(),
            'product_id' => $this->faker->randomNumber(),
        ];
    }
}