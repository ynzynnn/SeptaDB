<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DatabaseProduct;
use App\Models\Order;
use App\Models\UserDatabase;
use App\Models\ActivityLog;
use App\Services\DatabaseProvisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PaymenterController extends Controller
{
    /**
     * Create / Provision a database instance from Paymenter
     */
    public function provision(Request $request, DatabaseProvisionService $provisioner)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string',
            'product_id' => 'nullable|integer',
            'product_type' => 'nullable|string',
            'external_id' => 'required|string', // Paymenter Service ID / Order ID
            'database_name' => 'nullable|string|max:50',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // 1. Locate or create user
        $user = User::firstOrCreate(
            ['email' => strtolower($validated['email'])],
            [
                'name' => $validated['name'] ?? explode('@', $validated['email'])[0],
                'username' => 'u_' . Str::lower(Str::random(8)),
                'password' => Hash::make(Str::random(16)),
                'role' => 'user',
                'status' => 'active',
                'balance' => 0,
            ]
        );

        // 2. Locate product by ID or type
        $product = null;
        if (!empty($validated['product_id'])) {
            $product = DatabaseProduct::find($validated['product_id']);
        }
        if (!$product && !empty($validated['product_type'])) {
            $product = DatabaseProduct::where('type', strtolower($validated['product_type']))->first();
        }
        if (!$product) {
            $product = DatabaseProduct::first(); // Fallback to first available package
        }

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'No matching database product package found in panel.',
            ], 404);
        }

        // 3. Create Order
        $order = Order::create([
            'order_number' => 'PAY-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'database_product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'approved',
            'source' => 'paymenter',
            'external_id' => $validated['external_id'],
            'notes' => $validated['notes'] ?? 'Order provisioned via Paymenter Integration.',
        ]);

        // 4. Provision database
        $userDatabase = $provisioner->provision($user, $product, $order, [
            'database_name' => $validated['database_name'] ?? null,
            'expires_at' => !empty($validated['expires_at']) ? $validated['expires_at'] : null,
            'notes' => 'Paymenter Service ID: ' . $validated['external_id'],
        ]);

        ActivityLog::log(
            $user->id,
            'PAYMENTER_PROVISION',
            "Database provisioned via Paymenter for Service #{$validated['external_id']}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Database successfully provisioned for Paymenter service.',
            'data' => [
                'id' => $userDatabase->id,
                'uuid' => $userDatabase->uuid,
                'external_id' => $validated['external_id'],
                'user_id' => $user->id,
                'user_email' => $user->email,
                'host' => $userDatabase->host,
                'port' => $userDatabase->port,
                'type' => $userDatabase->type,
                'database' => $userDatabase->database_name,
                'username' => $userDatabase->database_username,
                'password' => $userDatabase->decrypted_password,
                'connection_string' => $userDatabase->connection_string,
                'phpmyadmin_url' => $userDatabase->phpmyadmin_url,
                'status' => $userDatabase->status,
                'created_at' => $userDatabase->created_at,
            ],
        ], 201);
    }

    /**
     * Suspend database when invoice is overdue in Paymenter
     */
    public function suspend(Request $request, DatabaseProvisionService $provisioner)
    {
        $validated = $request->validate([
            'identifier' => 'required|string', // external_id, uuid, or id
        ]);

        $db = $this->findDatabase($validated['identifier']);

        if (!$db) {
            return response()->json(['success' => false, 'message' => 'Database instance not found.'], 404);
        }

        $provisioner->suspend($db);

        return response()->json([
            'success' => true,
            'message' => "Database {$db->database_name} has been suspended.",
            'status' => 'suspended',
        ]);
    }

    /**
     * Unsuspend database when invoice is paid in Paymenter
     */
    public function unsuspend(Request $request, DatabaseProvisionService $provisioner)
    {
        $validated = $request->validate([
            'identifier' => 'required|string',
        ]);

        $db = $this->findDatabase($validated['identifier']);

        if (!$db) {
            return response()->json(['success' => false, 'message' => 'Database instance not found.'], 404);
        }

        $provisioner->unsuspend($db);

        return response()->json([
            'success' => true,
            'message' => "Database {$db->database_name} has been unsuspended.",
            'status' => 'active',
        ]);
    }

    /**
     * Terminate database when service is cancelled or expired in Paymenter
     */
    public function terminate(Request $request, DatabaseProvisionService $provisioner)
    {
        $validated = $request->validate([
            'identifier' => 'required|string',
        ]);

        $db = $this->findDatabase($validated['identifier']);

        if (!$db) {
            return response()->json(['success' => false, 'message' => 'Database instance not found.'], 404);
        }

        $provisioner->terminate($db);

        return response()->json([
            'success' => true,
            'message' => "Database {$db->database_name} has been terminated.",
            'status' => 'terminated',
        ]);
    }

    /**
     * Query status of database
     */
    public function status($identifier)
    {
        $db = $this->findDatabase($identifier);

        if (!$db) {
            return response()->json(['success' => false, 'message' => 'Database instance not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $db->id,
                'uuid' => $db->uuid,
                'status' => $db->status,
                'host' => $db->host,
                'port' => $db->port,
                'database' => $db->database_name,
                'username' => $db->database_username,
                'password' => $db->decrypted_password,
                'connection_string' => $db->connection_string,
                'phpmyadmin_url' => $db->phpmyadmin_url,
            ],
        ]);
    }

    /**
     * Helper to find database by external_id, uuid, or numeric id
     */
    protected function findDatabase(string $identifier): ?UserDatabase
    {
        // 1. Try by Order external_id
        $order = Order::where('external_id', $identifier)->first();
        if ($order && $order->userDatabase) {
            return $order->userDatabase;
        }

        // 2. Try by UUID
        $byUuid = UserDatabase::where('uuid', $identifier)->first();
        if ($byUuid) {
            return $byUuid;
        }

        // 3. Try by numeric ID
        if (is_numeric($identifier)) {
            return UserDatabase::find($identifier);
        }

        return null;
    }
}
