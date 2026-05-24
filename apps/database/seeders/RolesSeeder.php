<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('scientist', 'web');
        Role::findOrCreate('user', 'web');
    }
}
