<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserDatabase;
use App\Models\DatabaseProduct;
use App\Models\ActivityLog;
use App\Services\DatabaseProvisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $databases = UserDatabase::with('product')
            ->where('user_id', $userId)
            ->where('status', '!=', 'terminated')
            ->latest()
            ->get()
            ->transform(function ($db) {
                $db->password = $db->decrypted_password;
                $db->connection_string = $db->connection_string;
                return $db;
            });

        return response()->json([
            'success' => true,
            'databases' => $databases,
        ]);
    }

    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;

        $database = UserDatabase::with('product')
            ->where('user_id', $userId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->firstOrFail();

        $database->password = $database->decrypted_password;
        $database->connection_string = $database->connection_string;

        return response()->json([
            'success' => true,
            'database' => $database,
        ]);
    }

    /**
     * Direct Database Creation (Pure management, no billing)
     */
    public function store(Request $request, DatabaseProvisionService $provisioner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|in:mysql,mariadb,postgresql,mongodb,redis',
            'database_name' => 'nullable|string|max:40|regex:/^[a-zA-Z0-9_]+$/',
        ]);

        $user = $request->user();

        // Find or create default product template for this engine
        $product = DatabaseProduct::firstOrCreate(
            ['type' => strtolower($validated['type'])],
            [
                'name' => strtoupper($validated['type']) . ' Server',
                'host' => env('DB_PUBLIC_HOST', '127.0.0.1'),
                'port' => strtolower($validated['type']) === 'postgresql' ? 5432 : 
                         (strtolower($validated['type']) === 'mongodb' ? 27017 : 
                         (strtolower($validated['type']) === 'redis' ? 6379 : 3306)),
                'status' => 'available',
                'auto_provision' => true,
                'phpmyadmin_url' => in_array(strtolower($validated['type']), ['mysql', 'mariadb']) ? env('PHPMYADMIN_PUBLIC_URL', 'http://localhost/phpmyadmin') : null,
            ]
        );

        $userDb = $provisioner->provision($user, $product, null, [
            'name' => $validated['name'],
            'database_name' => $validated['database_name'] ?? null,
            'notes' => 'Created via user panel',
        ]);

        ActivityLog::log(
            $user->id,
            'DATABASE_CREATED',
            "User created database {$userDb->database_name} ({$userDb->type})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Database provisioned successfully.',
            'database' => [
                'id' => $userDb->id,
                'uuid' => $userDb->uuid,
                'name' => $userDb->name,
                'type' => $userDb->type,
                'host' => $userDb->host,
                'port' => $userDb->port,
                'database_name' => $userDb->database_name,
                'database_username' => $userDb->database_username,
                'password' => $userDb->decrypted_password,
                'connection_string' => $userDb->connection_string,
                'phpmyadmin_url' => $userDb->phpmyadmin_url,
                'status' => $userDb->status,
            ],
        ], 201);
    }

    public function resetPassword(Request $request, $id)
    {
        $userId = $request->user()->id;

        $database = UserDatabase::where('user_id', $userId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->firstOrFail();

        $newPassword = Str::random(16) . '!' . rand(10, 99);

        // Update in MySQL server if applicable
        if (in_array(strtolower($database->type), ['mysql', 'mariadb'])) {
            try {
                $quotedPass = DB::getPdo()->quote($newPassword);
                DB::statement("ALTER USER IF EXISTS '{$database->database_username}'@'%' IDENTIFIED BY {$quotedPass}");
                DB::statement("FLUSH PRIVILEGES");
            } catch (\Exception $e) {
                // Log warning
            }
        }

        $database->update([
            'database_password' => Crypt::encryptString($newPassword),
        ]);

        ActivityLog::log(
            $userId,
            'DATABASE_PASSWORD_RESET',
            "User reset password for database: {$database->database_name}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Database password reset successfully.',
            'new_password' => $newPassword,
            'connection_string' => $database->fresh()->connection_string,
        ]);
    }

    public function destroy(Request $request, $id, DatabaseProvisionService $provisioner)
    {
        $userId = $request->user()->id;

        $database = UserDatabase::where('user_id', $userId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->firstOrFail();

        $provisioner->terminate($database);

        return response()->json([
            'success' => true,
            'message' => 'Database deleted successfully.',
        ]);
    }
}
