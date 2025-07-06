<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GRNNote extends Model
{
    use HasFactory;

    protected $table = 'grn_notes'; // Add this line to specify the correct table name

    protected $fillable = [
        'grn_number',
        'product_id',
        'supplier_id',
        'admin_id',
        'price',
        'product_details',
        'received_date'
    ];

    protected $casts = [
        'product_details' => 'array',
        'received_date' => 'date',
        'price' => 'decimal:2'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
