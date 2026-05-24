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
    private const REPORTS_PATH = 'reports';

    public function handle(ImportCompleted $event): void
    {
        $import = $event->getImport();

        if ($import->importer !== TaxonImporter::class) {
            return;
        }

        if (! $import->user_id) {
            return;
        }

        $duplicates = Cache::get("taxon-import-duplicates-{$import->getKey()}", []);

        if (! empty($duplicates)) {
            $this->handleDuplicates($import, $duplicates);
        }

        $this->cacheImportStats($import, count($duplicates));
        $this->notifyUser($import->user_id);
    }

    private function handleDuplicates(mixed $import, array $duplicates): void
    {
        $reportPath = $this->generateDuplicateReport($import->getKey(), $duplicates);
        $this->sendDuplicateNotification($import->user_id, $reportPath, count($duplicates));
    }

    private function generateDuplicateReport(int $importId, array $duplicates): string
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
        Storage::disk('public')->put(self::REPORTS_PATH."/{$filename}", implode(PHP_EOL, $lines));

        return self::REPORTS_PATH."/{$filename}";
    }

    private function sendDuplicateNotification(int $userId, string $reportPath, int $duplicateCount): void
    {
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Duplicate taxa detected')
            ->body($this->getDuplicateMessage($duplicateCount))
            ->warning()
            ->actions([
                Action::make('download')
                    ->label('Download report')
                    ->url(Storage::disk('public')->url($reportPath), shouldOpenInNewTab: true),
            ])
            ->sendToDatabase($user);
    }

    private function getDuplicateMessage(int $count): string
    {
        $taxonLabel = $count === 1 ? 'taxon' : 'taxa';

        return "{$count} {$taxonLabel} were not imported because they already exist in the database.";
    }

    private function cacheImportStats(mixed $import, int $duplicateCount): void
    {
        Cache::put("taxon-import-completed-{$import->user_id}", [
            'successful_rows' => $import->successful_rows,
            'failed_rows' => $import->getFailedRowsCount(),
            'duplicate_rows' => $duplicateCount,
            'completed_at' => now()->toIso8601String(),
        ], now()->addMinutes(5));
    }

    private function notifyUser(int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            event(new DatabaseNotificationsSent($user));
        }
    }
}
