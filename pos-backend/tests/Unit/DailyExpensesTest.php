<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\DailyExpenses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class DailyExpensesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_daily_expense()
    {
        $expenseData = [
            'category' => 'Utilities',
            'custom_category' => 'Electricity',
            'description' => 'Monthly electricity bill',
            'amount' => 150.50,
            'date' => '2024-01-15',
        ];

        $expense = DailyExpenses::create($expenseData);

        $this->assertDatabaseHas('daily_expenses', $expenseData);
        $this->assertEquals($expenseData['category'], $expense->category);
        $this->assertEquals($expenseData['amount'], $expense->amount);
    }

    public function test_can_create_daily_expense_using_factory()
    {
        $expense = DailyExpenses::factory()->create();

        $this->assertNotNull($expense->id);
        $this->assertDatabaseHas('daily_expenses', [
            'id' => $expense->id,
            'category' => $expense->category,
            'amount' => $expense->amount,
        ]);
    }

    public function test_can_update_daily_expense()
    {
        $expense = DailyExpenses::factory()->create();
        
        $updatedData = [
            'category' => 'Updated Category',
            'amount' => 200.00,
        ];

        $expense->update($updatedData);

        $this->assertEquals('Updated Category', $expense->fresh()->category);
        $this->assertEquals(200.00, $expense->fresh()->amount);
    }

    public function test_can_delete_daily_expense()
    {
        $expense = DailyExpenses::factory()->create();
        
        $expenseId = $expense->id;
        $expense->delete();

        $this->assertDatabaseMissing('daily_expenses', ['id' => $expenseId]);
    }

    public function test_daily_expense_amount_is_decimal()
    {
        $expense = DailyExpenses::factory()->create([
            'amount' => 100.50
        ]);

        $this->assertIsString($expense->amount);
        $this->assertEquals('100.50', $expense->amount);
        $this->assertStringContainsString('.', $expense->amount);
    }
}
