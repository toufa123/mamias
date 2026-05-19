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
            'email' => 'atef.ouerghi@spa-rac.org',
            'password' => 'Toufa_251205',
            'role' => 'scientist',
        ],
        [
            'title' => null,
            'first_name' => 'Scientist',
            'last_name' => null,
            'email' => 'scientist@mamias.local',
            'password' => 'Toufa_251205',
            'role' => 'scientist',
        ],
        [
            'title' => null,
            'first_name' => 'Public',
            'last_name' => 'User',
            'email' => 'atef.ouerghi@gmail.com',
            'password' => 'Toufa_251205',
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
        $existing = User::where('email', $data['email'])->first();

        if ($existing) {
            $this->warn("User {$data['email']} already exists, skipping.");

            return;
        }

        $role = Role::findOrCreate($data['role'], 'web');

        $user = User::create([
            'title' => $data['title'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($role);

        $this->info("Created user: {$data['email']} ({$data['role']})");
    }
}
