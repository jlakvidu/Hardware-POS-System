<?php

namespace Database\Factories;

use App\Models\Investment;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    public function definition()
    {
        return [
            'investor_name' => $this->faker->name,
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'investment_date' => $this->faker->date(),
            'description' => $this->faker->sentence,
        ];
    }
}