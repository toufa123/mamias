<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Seed development users (admin, scientist, public) with their assigned roles. */
class DeveloperLoginUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make((string) config('developer-logins.default_password', 'password'));

        $admin = User::updateOrCreate(
            ['email' => config('services.dev_login.admin_email')],
            [
                'title' => 'Mr',
                'first_name' => 'Admin',
                'last_name' => 'MAMIAS',
                'name' => 'Admin MAMIAS',
                'country' => 'TN',
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );
        $admin->syncRoles(['super_admin']);

        $scientist = User::updateOrCreate(
            ['email' => config('services.dev_login.scientist_email')],
            [
                'title' => 'Dr',
                'first_name' => 'Scientist',
                'last_name' => 'MAMIAS',
                'name' => 'Scientist MAMIAS',
                'country' => 'TN',
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );
        $scientist->syncRoles(['scientist']);

        $publicUser = User::updateOrCreate(
            ['email' => config('services.dev_login.public_email')],
            [
                'title' => 'Mr',
                'first_name' => 'Public',
                'last_name' => 'User',
                'name' => 'Public User',
                'country' => 'TN',
                'email_verified_at' => now(),
                'password' => $password,
            ]
        );
        $publicUser->syncRoles(['user']);
    }
}
