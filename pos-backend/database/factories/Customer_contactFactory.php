<?php

namespace Database\Factories;

use App\Models\Customer_contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class Customer_contactFactory extends Factory
{
    protected $model = Customer_contact::class;

    public function definition()
    {
        return [
            'contact_number' => $this->faker->phoneNumber,
            'customer_id' => \App\Models\Customer::factory(),
        ];
    }
}