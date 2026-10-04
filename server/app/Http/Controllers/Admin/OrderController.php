<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ActivityLog;
use App\Services\DatabaseProvisionService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user:id,name,username,email', 'product:id,name,type', 'userDatabase:id,uuid,order_id,name,database_name,status']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('username', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    public function approve(Request $request, $id, DatabaseProvisionService $provisioner)
    {
        $order = Order::with(['user', 'product'])->findOrFail($id);

        if ($order->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already approved.',
            ], 400);
        }

        // Provision database for user
        $userDatabase = $provisioner->provision($order->user, $order->product, $order);

        $order->update(['status' => 'approved']);

        ActivityLog::log(
            $request->user()->id,
            'ORDER_APPROVED',
            "Admin approved order {$order->order_number} for user {$order->user->username}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Order approved and database provisioned successfully.',
            'order' => $order->fresh(['userDatabase']),
        ]);
    }

    public function reject(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reject an already approved order.',
            ], 400);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:255',
        ]);

        $order->update([
            'status' => 'rejected',
            'notes' => $validated['notes'] ?? 'Order rejected by administrator.',
        ]);

        ActivityLog::log(
            $request->user()->id,
            'ORDER_REJECTED',
            "Admin rejected order {$order->order_number}."
        );

        return response()->json([
            'success' => true,
            'message' => 'Order rejected.',
            'order' => $order,
        ]);
    }
}
