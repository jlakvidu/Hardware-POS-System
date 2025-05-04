<?php

namespace Database\Factories;

use App\Models\GRNNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class GRNNoteFactory extends Factory
{
    protected $model = GRNNote::class;

    public function definition()
    {
        return [
            'grn_number' => $this->faker->unique()->numerify('GRN-#####'),
            'product_id' => Product::factory(),
            'supplier_id' => Supplier::factory(),
            'admin_id' => Admin::factory(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'product_details' => $this->faker->text(),
            'received_date' => $this->faker->date(),
        ];
    }

    // Optional state methods for more control
    public function withProduct(Product $product)
    {
        return $this->state(function (array $attributes) use ($product) {
            return [
                'product_id' => $product->id,
            ];
        });
    }

    public function withSupplier(Supplier $supplier)
    {
        return $this->state(function (array $attributes) use ($supplier) {
            return [
                'supplier_id' => $supplier->id,
            ];
        });
    }
}