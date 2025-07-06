<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition()
    {
        return [
            'admin_id' => Admin::factory(),
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'contact' => $this->faker->phoneNumber,
        ];
    }
}
