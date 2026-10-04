<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDatabase;
use App\Models\DatabaseProduct;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\DatabaseProvisionService;
use Illuminate\Http\Request;

class AdminDatabaseController extends Controller
{
    public function index(Request $request)
    {
        $query = UserDatabase::with(['user:id,name,username,email', 'product:id,name,type']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('database_name', 'like', "%{$search}%")
                  ->orWhere('database_username', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $databases = $query->latest()->paginate(15);

        // Map Decrypted Password and Connection String for Admin view
        $databases->getCollection()->transform(function ($db) {
            $db->password = $db->decrypted_password;
            $db->connection_string = $db->connection_string;
            return $db;
        });

        return response()->json([
            'success' => true,
            'databases' => $databases,
        ]);
    }

    public function show($id)
    {
        $database = UserDatabase::with(['user', 'product'])->findOrFail($id);
        $database->password = $database->decrypted_password;
        $database->connection_string = $database->connection_string;

        return response()->json([
            'success' => true,
            'database' => $database,
        ]);
    }

    /**
     * Admin Direct Provision for any User
     */
    public function store(Request $request, DatabaseProvisionService $provisioner)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:50',
            'type' => 'required|in:mysql,mariadb,postgresql,mongodb,redis',
            'database_name' => 'nullable|string|max:40|regex:/^[a-zA-Z0-9_]+$/',
            'database_username' => 'nullable|string|max:30|regex:/^[a-zA-Z0-9_]+$/',
            'database_password' => 'nullable|string|min:6',
        ]);

        $user = User::findOrFail($validated['user_id']);

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
            'database_username' => $validated['database_username'] ?? null,
            'database_password' => $validated['database_password'] ?? null,
            'notes' => 'Allocated by administrator',
        ]);

        ActivityLog::log(
            $request->user()->id,
            'DATABASE_ALLOCATED_BY_ADMIN',
            "Admin created database {$userDb->database_name} for user {$user->username}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Database allocated to user successfully.',
            'database' => $userDb,
        ], 201);
    }

    public function suspend(Request $request, $id, DatabaseProvisionService $provisioner)
    {
        $db = UserDatabase::findOrFail($id);
        $provisioner->suspend($db);

        return response()->json([
            'success' => true,
            'message' => 'Database suspended.',
            'status' => 'suspended',
        ]);
    }

    public function unsuspend(Request $request, $id, DatabaseProvisionService $provisioner)
    {
        $db = UserDatabase::findOrFail($id);
        $provisioner->unsuspend($db);

        return response()->json([
            'success' => true,
            'message' => 'Database unsuspended.',
            'status' => 'active',
        ]);
    }

    public function terminate(Request $request, $id, DatabaseProvisionService $provisioner)
    {
        $db = UserDatabase::findOrFail($id);
        $provisioner->terminate($db);

        return response()->json([
            'success' => true,
            'message' => 'Database terminated and credentials removed.',
            'status' => 'terminated',
        ]);
    }
}
