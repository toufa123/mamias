<?php

namespace App\Listeners;

use App\Models\Taxon;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Support\Facades\Cache;

class TaxonImportCompletedListener
{
    public function handle(ImportCompleted $event): void
    {
        $import = $event->getImport();

        if ($import->importer !== \App\Filament\Imports\TaxonImporter::class) {
            return;
        }

        $userId = $import->user_id;

        if ($userId) {
            Cache::put("taxon-import-completed-{$userId}", [
                'successful_rows' => $import->successful_rows,
                'failed_rows' => $import->getFailedRowsCount(),
                'completed_at' => now()->toIso8601String(),
            ], now()->addMinutes(5));

            // Broadcast to trigger table refresh and notification polling in Filament
            $user = \App\Models\User::find($userId);
            if ($user) {
                event(new DatabaseNotificationsSent($user));
            }
        }
    }
}
