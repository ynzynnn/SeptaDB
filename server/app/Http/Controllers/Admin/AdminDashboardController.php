<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDatabase;
use App\Models\DatabaseProduct;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        $totalUsers = User::where('role', 'user')->count();
        $totalDatabases = UserDatabase::where('status', '!=', 'terminated')->count();
        $totalActiveDatabases = UserDatabase::where('status', 'active')->count();
        $totalEngines = 5; // MySQL, MariaDB, PostgreSQL, MongoDB, Redis

        // Recent databases deployed
        $recentDatabases = UserDatabase::with(['user:id,name,username,email'])
            ->where('status', '!=', 'terminated')
            ->latest()
            ->take(6)
            ->get();

        // Recent activity
        $recentActivities = ActivityLog::with('user:id,name,username')
            ->latest('id')
            ->take(8)
            ->get();

        // Database distribution by type
        $databasesByType = UserDatabase::select('type', DB::raw('count(*) as count'))
            ->where('status', '!=', 'terminated')
            ->groupBy('type')
            ->get();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_users' => $totalUsers,
                'total_databases' => $totalDatabases,
                'active_databases' => $totalActiveDatabases,
                'supported_engines' => $totalEngines,
            ],
            'recent_databases' => $recentDatabases,
            'recent_activities' => $recentActivities,
            'database_distribution' => $databasesByType,
        ]);
    }
}
