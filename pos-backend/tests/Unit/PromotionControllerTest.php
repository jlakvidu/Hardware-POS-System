<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionControllerTest extends TestCase
{
    use RefreshDatabase;

    private $promotion;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->product = Product::factory()->create();
        $this->promotion = Promotion::factory()->create([
            'product_id' => $this->product->id
        ]);
    }

    public function test_can_get_all_promotions()
    {
        $response = $this->getJson('/api/v1/promotions');

        $response->assertStatus(200)
                ->assertJsonCount(1)
                ->assertJsonStructure([
                    '*' => ['id', 'product_id', 'name', 'description', 'discount', 'start_date', 'end_date']
                ]);
    }

    public function test_can_get_single_promotion()
    {
        $response = $this->getJson("/api/v1/promotions/{$this->promotion->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $this->promotion->id,
                    'product_id' => $this->product->id
                ]);
    }

    public function test_can_create_promotion()
    {
        $promotionData = [
            'product_id' => $this->product->id,
            'name' => 'Test Promotion',
            'discount' => 10.00,
            'start_date' => '2024-01-01',
            'end_date' => '2024-02-01'
        ];

        $response = $this->postJson('/api/v1/promotions', $promotionData);

        $response->assertStatus(201)
                ->assertJsonFragment($promotionData);
    }

    public function test_can_update_promotion()
    {
        $updateData = [
            'name' => 'Updated Promotion',
            'discount' => 20.00
        ];

        $response = $this->putJson("/api/v1/promotions/{$this->promotion->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment($updateData);
    }

    public function test_can_delete_promotion()
    {
        $response = $this->deleteJson("/api/v1/promotions/{$this->promotion->id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Promotion deleted successfully']);
        
        $this->assertDatabaseMissing('promotions', ['id' => $this->promotion->id]);
    }

    public function test_returns_404_for_non_existent_promotion()
    {
        $response = $this->getJson("/api/v1/promotions/99999");

        $response->assertStatus(404)
                ->assertJson(['error' => 'Promotion not found']);
    }

    public function test_validates_required_fields_for_creation()
    {
        $response = $this->postJson('/api/v1/promotions', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['product_id', 'name', 'discount', 'start_date', 'end_date']);
    }
}
