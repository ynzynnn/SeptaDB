<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('panel:admin {email} {username} {password} {name=Administrator}', function (string $email, string $username, string $password, string $name) {
    $user = \App\Models\User::where('username', $username)->orWhere('email', $email)->first();
    
    if ($user) {
        $user->update([
            'email' => $email,
            'username' => $username,
            'name' => $name,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
        ]);
    } else {
        $user = \App\Models\User::create([
            'email' => $email,
            'username' => $username,
            'name' => $name,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
    
    $this->info("Admin account '{$user->username}' ({$user->email}) configured successfully.");
})->purpose('Create or update an administrator account for the panel');
