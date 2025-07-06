<?php

namespace Database\Factories;

use App\Models\SupplierAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierAdminFactory extends Factory
{
    protected $model = SupplierAdmin::class;

    public function definition()
    {
        return [
            'supplier_id' => $this->faker->randomNumber(),
            'admin_id' => $this->faker->randomNumber(),
            'alert_message' => $this->faker->sentence(),
        ];
    }
}