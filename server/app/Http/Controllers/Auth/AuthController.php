<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * User Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', // username or email
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');

        // Eloquent parameter binding ensures protection against SQL injection
        $user = User::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid username/email or password.'],
            ]);
        }

        if ($user->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact administrator.',
            ], 403);
        }

        // Generate personal access token
        $token = $user->createToken('auth-token')->plainTextToken;

        ActivityLog::log($user->id, 'USER_LOGIN', 'User logged in successfully.');

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'balance' => $user->balance,
            ],
        ]);
    }

    /**
     * User Registration
     */
    public function register(Request $request)
    {
        $allowReg = Setting::get('allow_registration', 'true');
        if ($allowReg === 'false') {
            return response()->json([
                'success' => false,
                'message' => 'User registration is currently disabled by administrator.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|min:3|max:30|alpha_dash|unique:users,username',
            'email' => 'required|string|email|max:100|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'status' => 'active',
            'balance' => 0,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        ActivityLog::log($user->id, 'USER_REGISTER', 'User registered new account.');

        return response()->json([
            'success' => true,
            'message' => 'Account registered successfully.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'balance' => $user->balance,
            ],
        ], 201);
    }

    /**
     * Current authenticated user profile
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'balance' => $user->balance,
                'databases_count' => $user->databases()->where('status', '!=', 'terminated')->count(),
                'orders_count' => $user->orders()->count(),
            ],
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
