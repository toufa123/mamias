<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:dev-db-self-heal')]
#[Description('Ensures the development database is in a consistent state.')]
class DevDbSelfHealCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting development database self-heal...');

        if (DB::getDriverName() === 'pgsql') {
            $this->syncUserSequence();
        }

        $this->info('Self-heal completed successfully.');

        return Command::SUCCESS;
    }

    private function syncUserSequence(): void
    {
        $this->comment('Syncing users table sequence...');
        
        $maxId = User::max('id') ?? 0;
        $nextId = $maxId + 1;

        DB::statement("SELECT setval('users_id_seq', $nextId, false)");
        
        $this->info("User sequence synced to next ID: {$nextId}");
    }
}
