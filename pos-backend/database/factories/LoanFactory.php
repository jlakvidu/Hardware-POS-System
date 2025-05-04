<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition()
    {
        return [
            'borrower_name' => $this->faker->name,
            'amount' => $this->faker->randomFloat(2, 1000, 10000),
            'loan_date' => $this->faker->date(),
            'due_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'description' => $this->faker->sentence,
        ];
    }
}