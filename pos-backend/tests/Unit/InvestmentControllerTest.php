<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Investment;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_can_get_all_investments()
    {
        Investment::factory()->count(3)->create();
        $response = $this->getJson('/api/v1/investments');
        $response->assertOk()->assertJsonCount(3);
    }

    public function test_can_get_single_investment()
    {
        $investment = Investment::factory()->create();
        $response = $this->getJson("/api/v1/investments/{$investment->id}");
        $response->assertOk()
            ->assertJson([
                'investor_name' => $investment->investor_name,
                'amount' => $investment->amount,
                'investment_date' => $investment->investment_date,
            ]);
    }

    public function test_can_create_investment()
    {
        $investmentData = [
            'investor_name' => 'John Doe',
            'amount' => 5000,
            'investment_date' => '2024-01-20',
            'description' => 'Test investment'
        ];

        $response = $this->postJson('/api/v1/investments', $investmentData);
        $response->assertCreated()->assertJson($investmentData);
    }

    public function test_can_update_investment()
    {
        $investment = Investment::factory()->create();
        $updateData = [
            'investor_name' => 'Jane Doe',
            'amount' => 7500,
            'investment_date' => '2024-01-21',
            'description' => 'Updated investment'
        ];

        $response = $this->putJson("/api/v1/investments/{$investment->id}", $updateData);
        $response->assertOk()->assertJson($updateData);
    }

    public function test_can_delete_investment()
    {
        $investment = Investment::factory()->create();
        $response = $this->deleteJson("/api/v1/investments/{$investment->id}");
        $response->assertOk()
            ->assertJson(['message' => 'Investment deleted successfully']);
        $this->assertDatabaseMissing('investments', ['id' => $investment->id]);
    }
}
