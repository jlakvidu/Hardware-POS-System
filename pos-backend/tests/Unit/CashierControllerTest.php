<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cashier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CashierControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $baseUrl = 'http://localhost:8000/api/v1';

    public function test_can_get_all_cashiers()
    {
        $cashiers = Cashier::factory()->count(3)->create();
        
        $response = $this->getJson($this->baseUrl . '/cashiers');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'email', 'image_url']
                    ]
                ]);
    }

    public function test_can_create_cashier()
    {
        Storage::fake('public');
        
        $cashierData = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => 'password123',
        ];

        $response = $this->postJson($this->baseUrl . '/cashiers', $cashierData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => ['id', 'name', 'email']
                ]);
    }

    public function test_can_create_cashier_with_image()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('avatar.jpg', 100);
        
        $cashierData = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => 'password123',
            'image' => $file
        ];

        $response = $this->postJson($this->baseUrl . '/cashiers', $cashierData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => ['id', 'name', 'email', 'image_url']
                ]);
    }

    public function test_can_show_cashier()
    {
        $cashier = Cashier::factory()->create();

        $response = $this->getJson($this->baseUrl . '/cashiers/' . $cashier->id);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'id',
                    'data' => ['id', 'name', 'email']
                ]);
    }

    public function test_can_update_cashier()
    {
        $cashier = Cashier::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@email.com',
            'password' => 'newpassword123'
        ];

        $response = $this->putJson($this->baseUrl . '/cashiers/' . $cashier->id, $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'id'
                ]);
    }

    public function test_can_delete_cashier()
    {
        $cashier = Cashier::factory()->create();

        $response = $this->deleteJson($this->baseUrl . '/cashiers/' . $cashier->id);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'id'
                ]);
    }

    public function test_can_update_cashier_image()
    {
        Storage::fake('public');
        
        $cashier = Cashier::factory()->create();
        $file = UploadedFile::fake()->create('new_avatar.jpg', 100);
        
        $response = $this->postJson(
            $this->baseUrl . '/cashiers/' . $cashier->id . '/image',
            ['image' => $file]
        );

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'id'
                ]);
    }

    public function test_can_update_password()
    {
        $cashier = Cashier::factory()->create([
            'password' => bcrypt('oldpassword123')
        ]);

        $response = $this->postJson($this->baseUrl . '/cashiers/change-password', [
            'id' => $cashier->id,
            'email' => $cashier->email,
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'id'
                ]);
    }
}
