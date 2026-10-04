<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseProduct;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = DatabaseProduct::query();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $products = $query->withCount(['userDatabases' => function ($q) {
                $q->where('status', 'active');
            }])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:mysql,mariadb,postgresql,mongodb,redis',
            'description' => 'nullable|string',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'default_db_name' => 'nullable|string|max:50',
            'default_db_username' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:available,sold_out,maintenance',
            'auto_provision' => 'boolean',
            'phpmyadmin_url' => 'nullable|url',
        ]);

        $product = DatabaseProduct::create($validated);

        ActivityLog::log($request->user()->id, 'PRODUCT_CREATED', "Admin created database product: {$product->name}");

        return response()->json([
            'success' => true,
            'message' => 'Database product created successfully.',
            'product' => $product,
        ], 201);
    }

    public function show($id)
    {
        $product = DatabaseProduct::with(['userDatabases.user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'product' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = DatabaseProduct::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:mysql,mariadb,postgresql,mongodb,redis',
            'description' => 'nullable|string',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'default_db_name' => 'nullable|string|max:50',
            'default_db_username' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:available,sold_out,maintenance',
            'auto_provision' => 'boolean',
            'phpmyadmin_url' => 'nullable|url',
        ]);

        $product->update($validated);

        ActivityLog::log($request->user()->id, 'PRODUCT_UPDATED', "Admin updated database product: {$product->name}");

        return response()->json([
            'success' => true,
            'message' => 'Database product updated successfully.',
            'product' => $product,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $product = DatabaseProduct::findOrFail($id);
        $name = $product->name;
        $product->delete();

        ActivityLog::log($request->user()->id, 'PRODUCT_DELETED', "Admin deleted database product: {$name}");

        return response()->json([
            'success' => true,
            'message' => 'Database product deleted successfully.',
        ]);
    }
}
