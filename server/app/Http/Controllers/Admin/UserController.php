<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Parameterized search preventing SQL injection
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->withCount(['databases' => function ($q) {
                $q->where('status', '!=', 'terminated');
            }, 'orders'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|min:3|max:30|alpha_dash|unique:users,username',
            'email' => 'required|string|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
            'status' => 'required|in:active,suspended',
            'balance' => 'nullable|numeric|min:0',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'],
            'balance' => $validated['balance'] ?? 0,
        ]);

        ActivityLog::log(
            $request->user()->id,
            'USER_CREATED',
            "Admin created user: {$user->username} ({$user->email})"
        );

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'user' => $user,
        ], 201);
    }

    public function show($id)
    {
        $user = User::with(['databases.product', 'orders.product'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,user',
            'status' => 'required|in:active,suspended',
            'balance' => 'nullable|numeric|min:0',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'role' => $validated['role'],
            'status' => $validated['status'],
            'balance' => $validated['balance'] ?? $user->balance,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        ActivityLog::log($request->user()->id, 'USER_UPDATED', "Admin updated user: {$user->username}");

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    public function toggleSuspend(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent self suspension
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot suspend your own account.',
            ], 400);
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        ActivityLog::log(
            $request->user()->id,
            $newStatus === 'suspended' ? 'USER_SUSPENDED' : 'USER_UNSUSPENDED',
            "Admin toggled user {$user->username} to {$newStatus}"
        );

        return response()->json([
            'success' => true,
            'message' => "User {$newStatus} successfully.",
            'status' => $newStatus,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 400);
        }

        $username = $user->username;
        $user->delete();

        ActivityLog::log($request->user()->id, 'USER_DELETED', "Admin deleted user: {$username}");

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
