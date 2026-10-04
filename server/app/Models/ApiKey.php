<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key_prefix',
        'key_hash',
        'permissions',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Generate a new API Key pair: plaintext token (shown only once) and DB model
     */
    public static function generate(string $name, array $permissions = ['*']): array
    {
        $rawKey = 'dbm_' . Str::random(40);
        $prefix = substr($rawKey, 0, 8);
        $hash = hash('sha256', $rawKey);

        $apiKey = self::create([
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => $hash,
            'permissions' => $permissions,
            'is_active' => true,
        ]);

        return [
            'apiKey' => $apiKey,
            'plainTextToken' => $rawKey,
        ];
    }

    /**
     * Verify token
     */
    public static function verify(string $rawKey): ?self
    {
        $hash = hash('sha256', $rawKey);
        $key = self::where('key_hash', $hash)->where('is_active', true)->first();

        if ($key) {
            $key->update(['last_used_at' => now()]);
        }

        return $key;
    }
}
