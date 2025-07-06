<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\GRNNote;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GRNNoteFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected $product;
    protected $supplier;
    protected $admin;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create required related models
        $this->product = Product::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->admin = Admin::factory()->create();
    }

    public function test_grn_note_factory_creates_valid_instance()
    {
        $grnNote = GRNNote::factory()
            ->withProduct($this->product)
            ->withSupplier($this->supplier)
            ->create([
                'admin_id' => $this->admin->id
            ]);

        $this->assertInstanceOf(GRNNote::class, $grnNote);
        $this->assertNotNull($grnNote->grn_number);
        $this->assertMatchesRegularExpression('/^GRN-\d{5}$/', $grnNote->grn_number);
        $this->assertIsInt($grnNote->product_id);
        $this->assertIsInt($grnNote->supplier_id);
        $this->assertIsInt($grnNote->admin_id);
        $this->assertIsNumeric($grnNote->price);
        $this->assertIsString($grnNote->product_details);
        $this->assertNotNull($grnNote->received_date);
    }

    public function test_grn_note_factory_creates_unique_grn_numbers()
    {
        $grnNotes = GRNNote::factory()->count(3)->create();
        $grnNumbers = $grnNotes->pluck('grn_number')->toArray();
        
        $this->assertEquals(count($grnNumbers), count(array_unique($grnNumbers)));
    }

    public function test_grn_note_factory_creates_valid_price_range()
    {
        $grnNote = GRNNote::factory()->create();
        
        $this->assertGreaterThanOrEqual(10, $grnNote->price);
        $this->assertLessThanOrEqual(1000, $grnNote->price);
    }
}
