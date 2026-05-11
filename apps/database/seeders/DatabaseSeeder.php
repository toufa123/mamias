<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            DeveloperLoginUsersSeeder::class,
        ]);

        $publicUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'title' => 'Mr',
                'first_name' => 'Test',
                'last_name' => 'User',
                'name' => 'Test User',
                'country' => 'TN',
                'email_verified_at' => null,
                'password' => Hash::make((string) config('developer-logins.default_password', 'password')),
            ]
        );

        $publicUser->syncRoles(['user']);
    }
}
