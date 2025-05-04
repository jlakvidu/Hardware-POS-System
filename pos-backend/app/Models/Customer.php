<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'contact_number',
    ];

    public function customerContact()
    {
        return $this->hasMany(Customer_contact::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}
