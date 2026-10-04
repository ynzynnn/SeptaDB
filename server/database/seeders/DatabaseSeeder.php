<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DatabaseProduct;
use App\Models\UserDatabase;
use App\Models\ApiKey;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@dbmanager.local'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'balance' => 9999999,
            ]
        );

        // 2. Demo User
        $user = User::firstOrCreate(
            ['email' => 'user@dbmanager.local'],
            [
                'name' => 'John Doe',
                'username' => 'johndoe',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'status' => 'active',
                'balance' => 250000,
            ]
        );

        // 3. Database Products
        $mysqlProd = DatabaseProduct::firstOrCreate(
            ['name' => 'MySQL 8.4 Starter'],
            [
                'type' => 'mysql',
                'description' => 'Fast, reliable MySQL database. Ideal for web apps, WordPress, and bots. Includes phpMyAdmin access.',
                'host' => '127.0.0.1',
                'port' => 3306,
                'default_db_name' => 'db_user_',
                'default_db_username' => 'u_',
                'price' => 25000,
                'stock' => 50,
                'status' => 'available',
                'auto_provision' => true,
                'phpmyadmin_url' => 'http://localhost/phpmyadmin',
            ]
        );

        $mariaProd = DatabaseProduct::firstOrCreate(
            ['name' => 'MariaDB 11 High-Perf Cloud'],
            [
                'type' => 'mariadb',
                'description' => 'Optimized MariaDB instance with InnoDB tuning and fast query cache.',
                'host' => '127.0.0.1',
                'port' => 3306,
                'default_db_name' => 'maria_',
                'default_db_username' => 'maria_u_',
                'price' => 35000,
                'stock' => 30,
                'status' => 'available',
                'auto_provision' => true,
                'phpmyadmin_url' => 'http://localhost/phpmyadmin',
            ]
        );

        $pgProd = DatabaseProduct::firstOrCreate(
            ['name' => 'PostgreSQL 16 Enterprise'],
            [
                'type' => 'postgresql',
                'description' => 'Advanced relational SQL with JSONB support, full text indexing, and high concurrency.',
                'host' => '127.0.0.1',
                'port' => 5432,
                'default_db_name' => 'pg_app_',
                'default_db_username' => 'pg_u_',
                'price' => 50000,
                'stock' => 25,
                'status' => 'available',
                'auto_provision' => true,
                'phpmyadmin_url' => null,
            ]
        );

        $mongoProd = DatabaseProduct::firstOrCreate(
            ['name' => 'MongoDB 7 NoSQL Cluster'],
            [
                'type' => 'mongodb',
                'description' => 'Flexible document database for high throughput real-time APIs and analytics.',
                'host' => '127.0.0.1',
                'port' => 27017,
                'default_db_name' => 'mongo_db_',
                'default_db_username' => 'mongo_u_',
                'price' => 60000,
                'stock' => 15,
                'status' => 'available',
                'auto_provision' => true,
                'phpmyadmin_url' => null,
            ]
        );

        // 4. Sample provisioned database for demo user
        UserDatabase::firstOrCreate(
            ['uuid' => 'a1b2c3d4-e5f6-7a8b-9c0d-1e2f3a4b5c6d'],
            [
                'user_id' => $user->id,
                'database_product_id' => $mysqlProd->id,
                'name' => 'Production Web Database',
                'type' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database_name' => 'db_user_app101',
                'database_username' => 'u_app101',
                'database_password' => Crypt::encryptString('SecretP@ss2026!'),
                'status' => 'active',
                'phpmyadmin_url' => 'http://localhost/phpmyadmin',
                'notes' => 'Allocated for e-commerce website backend',
                'assigned_at' => now(),
            ]
        );

        // 5. Default API Key for Paymenter integration testing
        // Token prefix: dbm_paym, raw key: dbm_paymenter_default_secret_key_12345
        $testToken = 'dbm_paymenter_secret_key_demo_2026';
        ApiKey::firstOrCreate(
            ['name' => 'Paymenter Primary Billing'],
            [
                'key_prefix' => substr($testToken, 0, 8),
                'key_hash' => hash('sha256', $testToken),
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );

        // 6. Settings
        $defaultSettings = [
            'panel_name' => 'NexusDB Cloud',
            'panel_currency' => 'IDR',
            'support_email' => 'support@nexusdb.local',
            'maintenance_mode' => 'false',
            'allow_registration' => 'true',
            'auto_approve_orders' => 'true',
        ];

        foreach ($defaultSettings as $key => $val) {
            Setting::set($key, $val);
        }

        // 7. Activity Log
        ActivityLog::log($admin->id, 'SYSTEM_INIT', 'Database manager initialized with default admin and products.');
    }
}
