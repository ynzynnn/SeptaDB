<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index()
    {
        $keys = ApiKey::latest()->get();

        return response()->json([
            'success' => true,
            'api_keys' => $keys,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $result = ApiKey::generate($validated['name']);

        ActivityLog::log(
            $request->user()->id,
            'API_KEY_CREATED',
            "Admin created API key: {$validated['name']}"
        );

        return response()->json([
            'success' => true,
            'message' => 'API Key created successfully. Please copy this token now as it will not be shown again.',
            'api_key' => $result['apiKey'],
            'token' => $result['plainTextToken'],
        ], 201);
    }

    public function toggle(Request $request, $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->update(['is_active' => !$apiKey->is_active]);

        $status = $apiKey->is_active ? 'enabled' : 'disabled';

        ActivityLog::log(
            $request->user()->id,
            'API_KEY_TOGGLED',
            "Admin {$status} API key: {$apiKey->name}"
        );

        return response()->json([
            'success' => true,
            'message' => "API Key {$status} successfully.",
            'is_active' => $apiKey->is_active,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $name = $apiKey->name;
        $apiKey->delete();

        ActivityLog::log(
            $request->user()->id,
            'API_KEY_DELETED',
            "Admin deleted API key: {$name}"
        );

        return response()->json([
            'success' => true,
            'message' => 'API Key revoked and deleted.',
        ]);
    }
}
