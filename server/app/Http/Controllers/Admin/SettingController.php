<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'panel_name' => 'nullable|string|max:100',
            'support_email' => 'nullable|email',
            'maintenance_mode' => 'nullable|in:true,false',
            'allow_registration' => 'nullable|in:true,false',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, (string) $value);
        }

        ActivityLog::log($request->user()->id, 'SETTINGS_UPDATED', 'Admin updated panel system settings.');

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'settings' => Setting::all()->pluck('value', 'key'),
        ]);
    }
}
