<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Customer_contact;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $baseUrl = 'http://localhost:8000/api/v1/customers';

    public function test_can_get_all_customers()
    {
        Customer::factory()
            ->count(15)
            ->has(Customer_contact::factory())
            ->create();

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
                'last_page',
                'next_page_url',
                'prev_page_url',
            ]);
    }

    public function test_can_create_customer()
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'contact_number' => '1234567890'
        ];

        $response = $this->postJson($this->baseUrl, $customerData);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Successfully Saved a new Customer']);

        $this->assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('customer_contact', [
            'contact_number' => '1234567890',
        ]);
    }

    public function test_can_show_customer()
    {
        $customer = Customer::factory()
            ->has(Customer_contact::factory())
            ->create();

        $response = $this->getJson("{$this->baseUrl}/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => $customer->name,
                'email' => $customer->email,
            ]);
    }

    public function test_can_update_customer()
    {
        $customer = Customer::factory()
            ->has(Customer_contact::factory())
            ->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'contact_number' => '9876543210'
        ];

        $response = $this->putJson("{$this->baseUrl}/{$customer->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Successfully Updated Customer']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_can_delete_customer()
    {
        $customer = Customer::factory()
            ->has(Customer_contact::factory())
            ->create();

        $response = $this->deleteJson("{$this->baseUrl}/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Successfully Deleted Customer']);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertSoftDeleted('customer_contact', ['customer_id' => $customer->id]);
    }

    public function test_cannot_create_customer_with_invalid_data()
    {
        $invalidData = [
            'name' => '',
            'email' => 'not-an-email',
            'contact_number' => ''
        ];

        $response = $this->postJson($this->baseUrl, $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'contact_number']);
    }

    public function test_cannot_update_customer_with_duplicate_email()
    {
        $customer1 = Customer::factory()
            ->has(Customer_contact::factory())
            ->create();
        
        $customer2 = Customer::factory()
            ->has(Customer_contact::factory())
            ->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => $customer2->email,
            'contact_number' => '9876543210'
        ];

        $response = $this->putJson("{$this->baseUrl}/{$customer1->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
