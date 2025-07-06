<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase;

    private $baseUrl = 'http://localhost:8000/api/v1';
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_all_assets()
    {
        $assets = Asset::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson("{$this->baseUrl}/assets");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_create_asset()
    {
        $assetData = [
            'name' => 'Test Asset',
            'type' => 'Equipment',
            'location' => 'Main Office',
            'value' => 1000.00
        ];

        $response = $this->actingAs($this->user)
            ->postJson("{$this->baseUrl}/assets", $assetData);

        $response->assertStatus(201)
            ->assertJsonFragment($assetData);
    }

    public function test_can_show_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("{$this->baseUrl}/assets/{$asset->id}");

        $response->assertStatus(200)
            ->assertJson([
                'name' => $asset->name,
                'type' => $asset->type,
                'location' => $asset->location,
                'value' => $asset->value
            ]);
    }

    public function test_can_update_asset()
    {
        $asset = Asset::factory()->create();
        $updateData = [
            'name' => 'Updated Asset',
            'type' => 'Updated Type',
            'location' => 'New Location',
            'value' => 2000.00
        ];

        $response = $this->actingAs($this->user)
            ->putJson("{$this->baseUrl}/assets/{$asset->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);
    }

    public function test_can_delete_asset()
    {
        $asset = Asset::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("{$this->baseUrl}/assets/{$asset->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Asset deleted successfully']);

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    public function test_returns_404_for_non_existent_asset()
    {
        $response = $this->actingAs($this->user)
            ->getJson("{$this->baseUrl}/assets/9999");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Asset not found']);
    }

    public function test_validates_required_fields_on_create()
    {
        $response = $this->actingAs($this->user)
            ->postJson("{$this->baseUrl}/assets", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type', 'location', 'value']);
    }
}
