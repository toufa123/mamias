<?php

namespace App\Listeners;

use App\Filament\Imports\TaxonImporter;
use App\Models\User;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TaxonImportCompletedListener
{
    public function handle(ImportCompleted $event): void
    {
        $import = $event->getImport();

        if ($import->importer !== TaxonImporter::class) {
            return;
        }

        $userId = $import->user_id;
        $importId = $import->getKey();

        if ($userId) {
            $duplicates = Cache::get("taxon-import-duplicates-{$importId}", []);

            if (! empty($duplicates)) {
                $this->generateDuplicateReport($importId, $duplicates);
                $this->sendDuplicateNotification($userId, $importId, count($duplicates));
            }

            Cache::put("taxon-import-completed-{$userId}", [
                'successful_rows' => $import->successful_rows,
                'failed_rows' => $import->getFailedRowsCount(),
                'duplicate_rows' => count($duplicates),
                'completed_at' => now()->toIso8601String(),
            ], now()->addMinutes(5));

            $user = User::find($userId);
            if ($user) {
                event(new DatabaseNotificationsSent($user));
            }
        }
    }

    private function generateDuplicateReport(int $importId, array $duplicates): void
    {
        $lines = [
            'Duplicate Taxa Report',
            '=====================',
            'Generated: '.now()->format('Y-m-d H:i:s'),
            'Import ID: '.$importId,
            '',
            sprintf('%-60s  %s', 'Scientific Name', 'Database ID'),
            str_repeat('-', 80),
        ];

        foreach ($duplicates as $duplicate) {
            $lines[] = sprintf('%-60s  %s', $duplicate['scientificname'], $duplicate['id']);
        }

        $lines[] = '';
        $lines[] = 'Total duplicates: '.count($duplicates);

        $filename = "taxon-import-duplicates-{$importId}.txt";
        Storage::disk('public')->put("reports/{$filename}", implode(PHP_EOL, $lines));
    }

    private function sendDuplicateNotification(int $userId, int $importId, int $duplicateCount): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $filename = "taxon-import-duplicates-{$importId}.txt";
        $url = Storage::disk('public')->url("reports/{$filename}");

        Notification::make()
            ->title('Duplicate taxa detected')
            ->body("{$duplicateCount} taxon".($duplicateCount === 1 ? '' : 'a').' were not imported because they already exist in the database.')
            ->warning()
            ->actions([
                Action::make('download')
                    ->label('Download report')
                    ->url($url, shouldOpenInNewTab: true),
            ])
            ->sendToDatabase($user);
    }
}
