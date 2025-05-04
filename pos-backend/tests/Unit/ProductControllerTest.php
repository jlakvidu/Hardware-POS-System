<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Admin;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $baseUrl = 'http://localhost:8000/api/v1';

    public function test_can_get_all_products()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("{$this->baseUrl}/products");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'price',
                            'calculate_length',
                            'size'
                        ]
                    ]
                ]);
    }

    public function test_can_create_product()
    {
        $supplier = Supplier::factory()->create();
        $inventory = Inventory::factory()->create();
        $admin = Admin::factory()->create();

        $productData = [
            'name' => $this->faker->word,
            'supplier_id' => $supplier->id,
            'seller_price' => 100,
            'discount' => 0,
            'selling_discount' => 0,
            'price' => 150,
            'brand_name' => $this->faker->word,
            'tax' => 10,
            'size' => '10m',
            'color' => 'red',
            'description' => $this->faker->sentence,
            'bar_code' => $this->faker->unique()->ean13,
            'inventory_id' => $inventory->id,
            'admin_id' => $admin->id,
            'calculate_length' => true
        ];

        $response = $this->postJson("{$this->baseUrl}/products", $productData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'price'
                    ]
                ]);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create();

        $updateData = [
            'name' => 'Updated Product',
            'price' => 200
        ];

        $response = $this->putJson("{$this->baseUrl}/products/{$product->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonPath('data.name', 'Updated Product')
                ->assertJsonPath('data.price', 200);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("{$this->baseUrl}/products/{$product->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'message'
                ]);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_can_update_stock()
    {
        $inventory = Inventory::factory()->create(['quantity' => 100]);
        $product = Product::factory()->create(['inventory_id' => $inventory->id]);

        $response = $this->postJson("{$this->baseUrl}/products/update-stock", [
            'product_id' => $product->id,
            'quantity' => 10
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 90
        ]);
    }

    public function test_can_get_product_by_inventory_id()
    {
        $inventory = Inventory::factory()->create();
        $product = Product::factory()->create(['inventory_id' => $inventory->id]);

        $response = $this->getJson("{$this->baseUrl}/products/inventory/{$inventory->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'name',
                    'supplierDetails' => [
                        'name',
                        'email',
                        'contact'
                    ]
                ]);
    }

    public function test_can_calculate_product_quantity_with_size()
    {
        $inventory = Inventory::factory()->create(['quantity' => 100]);
        $product = Product::factory()->create([
            'inventory_id' => $inventory->id,
            'size' => '10.5',
            'calculate_length' => true
        ]);

        $response = $this->getJson("{$this->baseUrl}/products/{$product->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'quantity' => 1050.0  // 10.5 * 100
                    ]
                ]);

        // Test with calculate_length = false
        $product->update(['calculate_length' => false]);
        $response = $this->getJson("{$this->baseUrl}/products/{$product->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'quantity' => 100.0  // Raw quantity without multiplication
                    ]
                ]);
    }
}
