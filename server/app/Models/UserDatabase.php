<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Exception;

class UserDatabase extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'database_product_id',
        'order_id',
        'name',
        'type',
        'host',
        'port',
        'database_name',
        'database_username',
        'database_password',
        'status',
        'phpmyadmin_url',
        'notes',
        'assigned_at',
        'expires_at',
    ];

    protected $casts = [
        'port' => 'integer',
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(DatabaseProduct::class, 'database_product_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Safely decrypt password if encrypted, or return as-is
     */
    public function getDecryptedPasswordAttribute(): string
    {
        try {
            return Crypt::decryptString($this->database_password);
        } catch (Exception $e) {
            return $this->database_password;
        }
    }

    /**
     * Build connection string URI
     */
    public function getConnectionStringAttribute(): string
    {
        $password = $this->decrypted_password;
        $type = strtolower($this->type);

        if ($type === 'mysql' || $type === 'mariadb') {
            return "mysql://{$this->database_username}:{$password}@{$this->host}:{$this->port}/{$this->database_name}";
        } elseif ($type === 'postgresql') {
            return "postgresql://{$this->database_username}:{$password}@{$this->host}:{$this->port}/{$this->database_name}";
        } elseif ($type === 'mongodb') {
            return "mongodb://{$this->database_username}:{$password}@{$this->host}:{$this->port}/{$this->database_name}?authSource=admin";
        } elseif ($type === 'redis') {
            return "redis://:{$password}@{$this->host}:{$this->port}";
        }

        return "{$type}://{$this->database_username}:{$password}@{$this->host}:{$this->port}/{$this->database_name}";
    }
}
