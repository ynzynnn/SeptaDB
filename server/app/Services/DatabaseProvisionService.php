<?php

namespace App\Services;

use App\Models\DatabaseProduct;
use App\Models\Order;
use App\Models\User;
use App\Models\UserDatabase;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class DatabaseProvisionService
{
    /**
     * Provision a new database for a user based on product
     */
    public function provision(User $user, DatabaseProduct $product, ?Order $order = null, array $custom = []): UserDatabase
    {
        // 1. Generate clean identifiers (strictly alphanumeric + underscore)
        $cleanUsername = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($user->username ?? 'user'));
        $randomSuffix = Str::lower(Str::random(6));

        $dbName = !empty($custom['database_name']) 
            ? preg_replace('/[^a-zA-Z0-9_]/', '', $custom['database_name'])
            : ($product->default_db_name ? $product->default_db_name . $randomSuffix : "db_{$cleanUsername}_{$randomSuffix}");

        $dbUsername = !empty($custom['database_username'])
            ? preg_replace('/[^a-zA-Z0-9_]/', '', $custom['database_username'])
            : ($product->default_db_username ? $product->default_db_username . $randomSuffix : "u_{$cleanUsername}_{$randomSuffix}");

        $dbPassword = !empty($custom['database_password'])
            ? $custom['database_password']
            : Str::random(16) . '!' . rand(10, 99);

        // 2. If it's MySQL/MariaDB and running on localhost, attempt actual DB creation
        if (in_array(strtolower($product->type), ['mysql', 'mariadb'])) {
            $this->createMysqlDatabase($dbName, $dbUsername, $dbPassword);
        }

        // 3. Create UserDatabase record (Store encrypted password)
        $userDatabase = UserDatabase::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'database_product_id' => $product->id,
            'order_id' => $order?->id,
            'name' => $custom['name'] ?? ($product->name . ' - ' . Str::upper($randomSuffix)),
            'type' => $product->type,
            'host' => env('DB_PUBLIC_HOST') ?: ($product->host ?: '127.0.0.1'),
            'port' => $product->port,
            'database_name' => $dbName,
            'database_username' => $dbUsername,
            'database_password' => Crypt::encryptString($dbPassword),
            'status' => 'active',
            'phpmyadmin_url' => env('PHPMYADMIN_PUBLIC_URL') ?: $product->phpmyadmin_url,
            'notes' => $custom['notes'] ?? 'Auto-provisioned on ' . now()->toDateTimeString(),
            'assigned_at' => now(),
            'expires_at' => $custom['expires_at'] ?? now()->addDays(30),
        ]);

        // Decrement product stock if positive
        if ($product->stock > 0) {
            $product->decrement('stock');
        }

        ActivityLog::log(
            $user->id,
            'DATABASE_PROVISIONED',
            "Database {$dbName} ({$product->type}) provisioned successfully."
        );

        return $userDatabase;
    }

    /**
     * Safely create MySQL database and grant privileges
     * Anti SQL Injection: Strictly validate identifier names with regex whitelist
     */
    protected function createMysqlDatabase(string $dbName, string $username, string $password): void
    {
        // Enforce strict identifier whitelist
        if (!preg_match('/^[a-zA-Z0-9_]{3,64}$/', $dbName)) {
            throw new Exception("Invalid database name format.");
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
            throw new Exception("Invalid database username format.");
        }

        try {
            // Using backticks on validated regex identifiers and PDO quote / parameterization for password
            $quotedPassword = DB::getPdo()->quote($password);

            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            DB::statement("CREATE USER IF NOT EXISTS '{$username}'@'%' IDENTIFIED BY {$quotedPassword}");
            DB::statement("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$username}'@'%'");
            DB::statement("FLUSH PRIVILEGES");
        } catch (Exception $e) {
            // Log but don't fail provisioning if DB server permissions are restricted
            ActivityLog::log(null, 'PROVISION_WARNING', "Could not execute MySQL CREATE: " . $e->getMessage());
        }
    }

    /**
     * Suspend user database access
     */
    public function suspend(UserDatabase $db): bool
    {
        $db->update(['status' => 'suspended']);

        // In MySQL, lock user account if possible
        try {
            if (in_array(strtolower($db->type), ['mysql', 'mariadb'])) {
                DB::statement("ALTER USER IF EXISTS '{$db->database_username}'@'%' ACCOUNT LOCK");
                DB::statement("FLUSH PRIVILEGES");
            }
        } catch (Exception $e) {
            // Ignore lock error
        }

        ActivityLog::log($db->user_id, 'DATABASE_SUSPENDED', "Database {$db->database_name} suspended.");
        return true;
    }

    /**
     * Unsuspend user database access
     */
    public function unsuspend(UserDatabase $db): bool
    {
        $db->update(['status' => 'active']);

        // In MySQL, unlock user account if possible
        try {
            if (in_array(strtolower($db->type), ['mysql', 'mariadb'])) {
                DB::statement("ALTER USER IF EXISTS '{$db->database_username}'@'%' ACCOUNT UNLOCK");
                DB::statement("FLUSH PRIVILEGES");
            }
        } catch (Exception $e) {
            // Ignore unlock error
        }

        ActivityLog::log($db->user_id, 'DATABASE_UNSUSPENDED', "Database {$db->database_name} unsuspended.");
        return true;
    }

    /**
     * Terminate user database
     */
    public function terminate(UserDatabase $db): bool
    {
        $db->update(['status' => 'terminated']);

        // In MySQL, drop database and user
        try {
            if (in_array(strtolower($db->type), ['mysql', 'mariadb'])) {
                if (preg_match('/^[a-zA-Z0-9_]+$/', $db->database_name) && preg_match('/^[a-zA-Z0-9_]+$/', $db->database_username)) {
                    DB::statement("DROP DATABASE IF EXISTS `{$db->database_name}`");
                    DB::statement("DROP USER IF EXISTS '{$db->database_username}'@'%'");
                    DB::statement("FLUSH PRIVILEGES");
                }
            }
        } catch (Exception $e) {
            // Ignore termination error
        }

        ActivityLog::log($db->user_id, 'DATABASE_TERMINATED', "Database {$db->database_name} terminated.");
        return true;
    }
}
