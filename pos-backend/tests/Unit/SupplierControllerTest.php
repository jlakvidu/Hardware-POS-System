<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Admin;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $baseUrl = 'http://localhost:8000/api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create user and admin
        $user = User::factory()->create([
            'role' => 'admin'
        ]);
        
        $this->admin = Admin::factory()->create([
            'user_id' => $user->id
        ]);

        // Authenticate the user
        Sanctum::actingAs($user, ['*']);
    }

    public function test_can_get_all_suppliers()
    {
        $suppliers = Supplier::factory()->count(3)->create();
        
        $response = $this->getJson($this->baseUrl . '/suppliers');
        
        $response->assertStatus(200)
                ->assertJsonCount(3);
    }

    public function test_can_store_supplier()
    {
        $supplierData = [
            'name' => $this->faker->company(),
            'email' => $this->faker->email(),
            'contact' => $this->faker->phoneNumber(),
        ];

        $response = $this->postJson($this->baseUrl . '/suppliers', $supplierData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'name' => $supplierData['name'],
                    'email' => $supplierData['email'],
                    'contact' => $supplierData['contact'],
                ]);
    }

    public function test_can_show_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->getJson($this->baseUrl . "/suppliers/{$supplier->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'email' => $supplier->email,
                    'contact' => $supplier->contact,
                ]);
    }

    public function test_can_show_supplier_products()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->getJson($this->baseUrl . "/suppliers/products/{$supplier->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([]);
    }

    public function test_can_update_supplier()
    {
        $supplier = Supplier::factory()->create();
        $updateData = [
            'name' => 'Updated Supplier Name',
            'contact' => '1234567890',
        ];

        $response = $this->putJson($this->baseUrl . "/suppliers/{$supplier->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment($updateData);
    }

    public function test_can_delete_supplier()
    {
        $supplier = Supplier::factory()->create();

        $response = $this->deleteJson($this->baseUrl . "/suppliers/{$supplier->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_cannot_create_supplier_with_invalid_data()
    {
        $response = $this->postJson($this->baseUrl . '/suppliers', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'contact']);
    }

    public function test_cannot_find_nonexistent_supplier()
    {
        $response = $this->getJson($this->baseUrl . '/suppliers/99999');

        $response->assertStatus(404)
                ->assertJson(['error' => 'Supplier not found']);
    }
}
