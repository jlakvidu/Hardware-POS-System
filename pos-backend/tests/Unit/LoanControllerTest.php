<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\LoanController;
use Illuminate\Http\Request;
use Mockery;

class LoanControllerTest extends TestCase
{
    use RefreshDatabase;

    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new LoanController();
    }

    public function test_index_returns_all_loans()
    {
        // Arrange
        Loan::factory()->count(3)->create();

        // Act
        $response = $this->controller->index();

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertCount(3, $response->getData());
    }

    public function test_show_returns_specific_loan()
    {
        // Arrange
        $loan = Loan::factory()->create();

        // Act
        $response = $this->controller->show($loan->id);

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertEquals($loan->borrower_name, $response->getData()->borrower_name);
    }

    public function test_show_returns_404_for_non_existent_loan()
    {
        // Act
        $response = $this->controller->show(999);

        // Assert
        $this->assertEquals(404, $response->status());
    }

    public function test_store_creates_new_loan()
    {
        // Arrange
        $loanData = [
            'borrower_name' => 'John Doe',
            'amount' => 1000,
            'loan_date' => '2023-01-01',
            'due_date' => '2023-02-01',
            'status' => 'pending',
            'description' => 'Test loan'
        ];
        $request = new Request($loanData);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $this->assertEquals(201, $response->status());
        $this->assertDatabaseHas('loans', $loanData);
    }

    public function test_update_modifies_existing_loan()
    {
        // Arrange
        $loan = Loan::factory()->create();
        $updateData = [
            'borrower_name' => 'Jane Doe',
            'amount' => 2000,
            'loan_date' => '2023-01-01',
            'due_date' => '2023-02-01',
            'status' => 'paid',
            'description' => 'Updated loan'
        ];
        $request = new Request($updateData);

        // Act
        $response = $this->controller->update($request, $loan->id);

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertDatabaseHas('loans', $updateData);
    }

    public function test_destroy_deletes_loan()
    {
        // Arrange
        $loan = Loan::factory()->create();

        // Act
        $response = $this->controller->destroy($loan->id);

        // Assert
        $this->assertEquals(200, $response->status());
        $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
