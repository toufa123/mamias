<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

#[Signature('app:create-filament-users')]
#[Description('Creates default Filament users for development.')]
class CreateFilamentUsersCommand extends Command
{
    private array $users = [
        [
            'title' => 'Mr',
            'first_name' => 'Atef',
            'last_name' => 'OUERGHI',
            'email_env' => 'DEV_LOGIN_ADMIN_EMAIL',
            'password_env' => 'DEV_LOGIN_ADMIN_PASSWORD',
            'role' => 'scientist',
        ],
        [
            'title' => null,
            'first_name' => 'Scientist',
            'last_name' => null,
            'email_env' => 'DEV_LOGIN_SCIENTIST_EMAIL',
            'password_env' => 'DEV_LOGIN_SCIENTIST_PASSWORD',
            'role' => 'scientist',
        ],
        [
            'title' => null,
            'first_name' => 'Public',
            'last_name' => 'User',
            'email_env' => 'DEV_LOGIN_PUBLIC_EMAIL',
            'password_env' => 'DEV_LOGIN_PUBLIC_PASSWORD',
            'role' => 'user',
        ],
    ];

    public function handle(): int
    {
        $this->info('Creating Filament users...');

        foreach ($this->users as $userData) {
            $this->createUser($userData);
        }

        $this->info('All users created successfully.');

        return Command::SUCCESS;
    }

    private function createUser(array $data): void
    {
        $email = config("app.{$data['email_env']}");
        $password = config("app.{$data['password_env']}");

        if (blank($email) || blank($password)) {
            $this->warn("Skipping {$data['first_name']} {$data['last_name']}: missing env config.");

            return;
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            $this->warn("User {$email} already exists, skipping.");

            return;
        }

        $role = Role::findOrCreate($data['role'], 'web');

        $user = User::create([
            'title' => $data['title'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($role);

        $this->info("Created user: {$email} ({$data['role']})");
    }
}
