<?php
    
    namespace Database\Seeders;
    
    use App\Models\User;
    use Illuminate\Database\Console\Seeds\WithoutModelEvents;
    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\Hash;
    use Spatie\Permission\Models\Role;
    
    class DatabaseSeeder extends Seeder
    {
        use WithoutModelEvents;
        
        /**
         * Seed the application's database.
         *
         * This seeder is idempotent: running it multiple times will not create
         * duplicate records. Developer-login accounts are always restored to their
         * expected state, making the setup self-healing after a DB reset.
         */
        public function run(): void
        {
            // ── 1. Ensure all required roles exist ───────────────────────────
            $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            $panelUserRole  = Role::firstOrCreate(['name' => 'panel_user',  'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            
            // ── 2. Developer-login accounts (local / staging only) ────────────
            // These accounts are auto-restored after a DB reset so developers
            // never need to run manual tinker commands. They are intentionally
            // excluded from production to keep seeded credentials out of live DBs.
            if (app()->isProduction()) {
                $this->command?->warn('[DatabaseSeeder] Skipping developer-login accounts in production.');
                
                return;
            }
            
            // This account maps to the "Admin" shortcut in FilamentDeveloperLoginsPlugin.
            $adminUser = User::updateOrCreate(
                ['email' => 'atef.ouerghi@spa-rac.org'],
                [
                    'first_name'         => 'Atef',
                    'last_name'          => 'Ouerghi',
                    'title'              => 'Dr',
                    'country'            => 'TN',
                    'password'           => Hash::make('password'),
                    'email_verified_at'  => now(),
                ]
            );
            $adminUser->syncRoles([$superAdminRole]);
            
            // This account maps to the "User" shortcut in FilamentDeveloperLoginsPlugin.
            $panelUser = User::updateOrCreate(
                ['email' => 'atef.ouerghi@gmail.com'],
                [
                    'first_name'         => 'Atef',
                    'last_name'          => 'Ouerghi (Panel)',
                    'title'              => 'Mr',
                    'country'            => 'TN',
                    'password'           => Hash::make('password'),
                    'email_verified_at'  => now(),
                ]
            );
            $panelUser->syncRoles([$panelUserRole]);
        }
    }
