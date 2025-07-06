<?php

namespace Tests\Feature\Controllers;

use App\Models\Sales;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SalesControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $baseUrl = 'http://localhost:8000/api/v1';
    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user and authenticate
        $this->user = User::factory()->create([
            'role' => 'cashier'
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    #[Test]
    public function it_can_list_all_sales()
    {
        $customer = Customer::factory()->create();
        Sales::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'cashier_id' => $this->user->id
        ]);

        $response = $this->getJson("{$this->baseUrl}/sales");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data'
                ]);
    }

    #[Test]
    public function it_can_create_a_sale()
    {
        $product = Product::factory()->create();
        $inventory = Inventory::factory()->create([
            'quantity' => 100
        ]);

        // Associate inventory with product
        $product->inventory()->create($inventory->toArray());

        $saleData = [
            'cashier_id' => $this->user->id,
            'payment_type' => 'CASH',
            'discount' => 10,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'price' => 100,
                    'product_discount' => 5
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson("{$this->baseUrl}/sales", $saleData);
        
        $response->assertStatus(201);
    }

    #[Test]
    public function it_can_show_a_sale()
    {
        $sale = Sales::factory()->create([
            'cashier_id' => $this->user->id
        ]);

        $response = $this->getJson("{$this->baseUrl}/sales/{$sale->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_update_a_sale()
    {
        $sale = Sales::factory()->create([
            'status' => 1,
            'cashier_id' => $this->user->id
        ]);
        
        // Create inventory first since Product needs inventory_id
        $inventory = Inventory::factory()->create([
            'quantity' => 100,
            'location' => 'Main Store',
            'status' => 'In Stock',
            'restock_date_time' => now(),
            'added_stock_amount' => 100
        ]);

        // Create product with inventory_id
        $product = Product::factory()->create([
            'inventory_id' => $inventory->id
        ]);

        $updateData = [
            'cashier_id' => $this->user->id,
            'payment_type' => 'CREDIT_CARD',
            'status' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'price' => 150
                ]
            ],
            'discount' => 5
        ];

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->putJson("{$this->baseUrl}/sales/{$sale->id}", $updateData);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_process_sales_return()
    {
        $sale = Sales::factory()->create([
            'status' => 1,
            'cashier_id' => $this->user->id
        ]);
        
        // Create inventory first since Product needs inventory_id
        $inventory = Inventory::factory()->create([
            'quantity' => 50,
            'location' => 'Main Store',
            'status' => 'In Stock',
            'restock_date_time' => now(),
            'added_stock_amount' => 50
        ]);

        // Create product with inventory_id
        $product = Product::factory()->create([
            'inventory_id' => $inventory->id
        ]);

        $returnData = [
            'cashier_id' => $this->user->id,
            'payment_type' => 'CASH',
            'status' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'reason' => 'Defective product'
                ]
            ]
        ];

        $response = $this->postJson("{$this->baseUrl}/return/sales/{$sale->id}", $returnData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'returns',
                        'updated_products'
                    ]
                ]);
    }

    #[Test]
    public function it_can_get_sales_report_for_today()
    {
        Sales::factory()->count(5)->create([
            'status' => 1,
            'cashier_id' => $this->user->id,
            'created_at' => now()
        ]);

        $response = $this->getJson("{$this->baseUrl}/reports/sales/today");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'sales_details',
                    'total_sales',
                    'total_income',
                    'total_customers',
                    'total_suppliers'
                ]);
    }

    #[Test]
    public function it_can_get_sales_reports_by_date_range()
    {
        Sales::factory()->count(10)->create([
            'status' => 1,
            'cashier_id' => $this->user->id
        ]);

        $response = $this->getJson("{$this->baseUrl}/reports/sales?from=" . 
            now()->subDays(7)->format('Y-m-d') . 
            "&to=" . now()->format('Y-m-d'));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_sales'
                ]);
    }

    #[Test]
    public function it_validates_required_fields_for_sale_creation()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson("{$this->baseUrl}/sales", []);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'cashier_id',
                    'payment_type',
                    'items',
                    'discount'
                ]);
    }

    #[Test]
    public function it_validates_payment_type()
    {
        $product = Product::factory()->create();
        
        $saleData = [
            'cashier_id' => $this->user->id,
            'payment_type' => 'INVALID_TYPE',
            'status' => 1,
            'discount' => 10,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'price' => 100
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson("{$this->baseUrl}/sales", $saleData);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors(['payment_type']);
    }
}
