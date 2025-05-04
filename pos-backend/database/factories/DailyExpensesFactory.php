<?php

namespace Database\Factories;

use App\Models\DailyExpenses;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyExpensesFactory extends Factory
{
    protected $model = DailyExpenses::class;

    public function definition()
    {
        return [
            'category' => $this->faker->word,
            'custom_category' => $this->faker->word,
            'description' => $this->faker->sentence,
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'date' => $this->faker->date,
        ];
    }
}