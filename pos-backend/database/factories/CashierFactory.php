<?php

namespace Database\Factories;

use App\Models\Cashier;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashierFactory extends Factory
{
    protected $model = Cashier::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'), // default password
            'image_path' => $this->faker->imageUrl(),
        ];
    }
}