<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'database_product_id',
        'amount',
        'status',
        'source',
        'external_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(DatabaseProduct::class, 'database_product_id');
    }

    public function userDatabase()
    {
        return $this->hasOne(UserDatabase::class);
    }
}
