<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $baseUrl = 'http://localhost:8000/api/v1';

    public function test_can_get_all_inventory()
    {
        $inventory = Inventory::factory()->create();
        
        $response = $this->getJson("{$this->baseUrl}/inventory");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    '*' => ['id', 'quantity', 'location', 'status', 'restock_date_time', 'product']
                ]);
    }

    public function test_can_create_inventory()
    {
        $inventoryData = [
            'quantity' => 100,
            'location' => 'Warehouse A',
            'status' => 'In Stock',
        ];

        $response = $this->postJson("{$this->baseUrl}/inventory", $inventoryData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'quantity',
                    'location',
                    'status',
                    'restock_date_time'
                ]);
    }

    public function test_can_show_inventory()
    {
        $inventory = Inventory::factory()->create();

        $response = $this->getJson("{$this->baseUrl}/inventory/{$inventory->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $inventory->id,
                    'quantity' => $inventory->quantity,
                    'location' => $inventory->location,
                    'status' => $inventory->status,
                ]);
    }

    public function test_can_update_inventory()
    {
        $inventory = Inventory::factory()->create();
        
        $updateData = [
            'quantity' => 150,
            'location' => 'Updated Location',
            'status' => 'In Stock',
            'restock_date_time' => now()->toDateTimeString(),
            'added_stock_amount' => 50
        ];

        $response = $this->putJson("{$this->baseUrl}/inventory/{$inventory->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'quantity',
                    'location',
                    'status',
                    'added_stock_amount',
                    'restock_date_time'
                ]);
    }

    public function test_can_delete_inventory()
    {
        $inventory = Inventory::factory()->create();

        $response = $this->deleteJson("{$this->baseUrl}/inventory/{$inventory->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Inventory deleted successfully']);
    }

    public function test_can_get_low_stock_items()
    {
        $inventory = Inventory::factory()->create(['quantity' => 10]);
        
        $response = $this->getJson("{$this->baseUrl}/inventory/low-stock");
        
        $response->assertStatus(200);
    }

    public function test_can_get_out_of_stock_items()
    {
        $inventory = Inventory::factory()->create(['quantity' => 0]);
        
        $response = $this->getJson("{$this->baseUrl}/inventory/out-of-stock");
        
        $response->assertStatus(200);
    }

    public function test_can_get_in_stock_items()
    {
        $inventory = Inventory::factory()->create(['quantity' => 100]);
        
        $response = $this->getJson("{$this->baseUrl}/inventory/in-stock");
        
        $response->assertStatus(200);
    }

    public function test_can_export_inventory_data()
    {
        $inventory = Inventory::factory()->create();
        
        $response = $this->getJson("{$this->baseUrl}/inventory/export-data");
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'summary' => [
                        'total_value',
                        'total_profit'
                    ]
                ]);
    }

    public function test_validation_fails_with_invalid_data()
    {
        $invalidData = [
            'quantity' => 'not-a-number',
            'location' => '',
            'status' => 'Invalid Status'
        ];

        $response = $this->postJson("{$this->baseUrl}/inventory", $invalidData);

        $response->assertStatus(422);
    }
}
