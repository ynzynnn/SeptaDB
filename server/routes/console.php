<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('panel:admin {email} {username} {password} {name=Administrator}', function (string $email, string $username, string $password, string $name) {
    $user = \App\Models\User::updateOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'username' => $username,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
        ]
    );
    $this->info("Admin account '{$user->username}' ({$user->email}) created successfully.");
})->purpose('Create or update an administrator account for the panel');
