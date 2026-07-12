<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/** Seed the Spatie roles: super_admin, scientist and user. */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('scientist', 'web');
        Role::findOrCreate('user', 'web');
    }
}
