<?php

namespace App\Listeners;

use App\Filament\Imports\TaxonImporter;
use App\Models\User;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Notifications\Events\DatabaseNotificationsSent;

class TaxonImportCompletedListener
{
    public function handle(ImportCompleted $event): void
    {
        $import = $event->getImport();

        if ($import->importer !== TaxonImporter::class) {
            return;
        }

        $user = $import->user_id ? User::find($import->user_id) : null;

        // Broadcast to trigger table refresh and notification polling in Filament
        if ($user) {
            event(new DatabaseNotificationsSent($user));
        }
    }
}
