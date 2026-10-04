<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DatabaseProduct;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Services\DatabaseProvisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function catalog()
    {
        $products = DatabaseProduct::where('status', '!=', 'maintenance')
            ->orderBy('price', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    public function order(Request $request, DatabaseProvisionService $provisioner)
    {
        $validated = $request->validate([
            'database_product_id' => 'required|exists:database_products,id',
            'custom_name' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $product = DatabaseProduct::findOrFail($validated['database_product_id']);

        if ($product->status === 'sold_out' || $product->stock <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, this database package is currently out of stock.',
            ], 400);
        }

        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'database_product_id' => $product->id,
            'amount' => $product->price,
            'status' => 'pending',
            'source' => 'panel',
            'notes' => $validated['notes'] ?? null,
        ]);

        $autoApprove = Setting::get('auto_approve_orders', 'true');

        if ($autoApprove === 'true') {
            $userDb = $provisioner->provision($user, $product, $order, [
                'name' => !empty($validated['custom_name']) ? $validated['custom_name'] : null,
            ]);

            $order->update(['status' => 'approved']);

            ActivityLog::log(
                $user->id,
                'ORDER_AUTO_APPROVED',
                "Order {$orderNumber} auto-approved and database provisioned."
            );

            return response()->json([
                'success' => true,
                'message' => 'Order completed and database provisioned instantly!',
                'order' => $order,
                'database' => $userDb,
            ], 201);
        }

        ActivityLog::log(
            $user->id,
            'ORDER_PLACED',
            "User placed order {$orderNumber} (Pending Admin Approval)."
        );

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully! Waiting for administrator approval.',
            'order' => $order,
        ], 201);
    }

    public function orders(Request $request)
    {
        $userId = $request->user()->id;

        $orders = Order::with(['product', 'userDatabase'])
            ->where('user_id', $userId)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }
}
