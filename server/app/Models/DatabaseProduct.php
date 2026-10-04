<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatabaseProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'host',
        'port',
        'default_db_name',
        'default_db_username',
        'default_db_password',
        'price',
        'stock',
        'status',
        'auto_provision',
        'phpmyadmin_url',
    ];

    protected $casts = [
        'port' => 'integer',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'auto_provision' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function userDatabases()
    {
        return $this->hasMany(UserDatabase::class);
    }
}
