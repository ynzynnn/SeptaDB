<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserDatabase;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function stats(Request $request)
    {
        $userId = $request->user()->id;

        $databasesCount = UserDatabase::where('user_id', $userId)
            ->where('status', '!=', 'terminated')
            ->count();

        $activeDatabases = UserDatabase::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        $recentDatabases = UserDatabase::where('user_id', $userId)
            ->where('status', '!=', 'terminated')
            ->latest()
            ->take(6)
            ->get()
            ->transform(function ($db) {
                $db->password = $db->decrypted_password;
                $db->connection_string = $db->connection_string;
                return $db;
            });

        return response()->json([
            'success' => true,
            'stats' => [
                'databases_count' => $databasesCount,
                'active_count' => $activeDatabases,
            ],
            'recent_databases' => $recentDatabases,
        ]);
    }
}
