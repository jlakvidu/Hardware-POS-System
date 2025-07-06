<?php

namespace Database\Factories;

use App\Models\SalesReturnItem;
use App\Models\ReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesReturnItemFactory extends Factory
{
    protected $model = SalesReturnItem::class;

    public function definition()
    {
        return [
            'return_item_id' => function () {
                return ReturnItem::factory()->create()->id;
            },
            'sales_id' => $this->faker->randomNumber(),
            'returned_at' => now(),
        ];
    }
}