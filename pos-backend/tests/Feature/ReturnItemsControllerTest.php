<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ReturnItem;
use App\Models\SalesReturnItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReturnItemsControllerTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = '/api/v1';

    public function test_can_get_all_return_items()
    {
        // Create test data
        $returnItem = ReturnItem::factory()->create();
        $salesReturnItem = SalesReturnItem::create([
            'return_item_id' => $returnItem->id,
            'sales_id' => 1,
            'returned_at' => now()
        ]);

        // Make request
        $response = $this->getJson("{$this->baseUrl}/return");

        // Assert response
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        '*' => [
                            'id',
                            'reason',
                            'quantity',
                            'product_id',
                            'created_at',
                            'updated_at',
                            'sales_id',
                            'returned_at'
                        ]
                    ]
                ]);
    }

    public function test_can_get_single_return_item()
    {
        // Create test data
        $returnItem = ReturnItem::factory()->create();

        // Make request
        $response = $this->getJson("{$this->baseUrl}/return/{$returnItem->id}");

        // Assert response
        $response->assertStatus(200)
                ->assertJson([
                    'id' => $returnItem->id,
                    'reason' => $returnItem->reason,
                    'quantity' => $returnItem->quantity,
                    'product_id' => $returnItem->product_id,
                ]);
    }

    public function test_returns_404_for_non_existent_return_item()
    {
        $nonExistentId = 99999;
        
        $response = $this->getJson("{$this->baseUrl}/return/{$nonExistentId}");

        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Return item not found',
                    'id' => $nonExistentId
                ]);
    }
}
