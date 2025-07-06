<?php

namespace Database\Factories;

use App\Models\Feedback;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition()
    {
        return [
            'customer_id' => $this->faker->randomNumber(),
            'comment' => $this->faker->sentence(),
            'rating' => $this->faker->numberBetween(1, 5),
        ];
    }
}