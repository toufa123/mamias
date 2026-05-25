<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupManager extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'tabler-database';

    protected string $view = 'filament.pages.backup-manager';

    protected static string|null|\UnitEnum $navigationGroup = 'System';

    protected static ?string $title = 'Backup Manager';

    public function getBackupFiles(?string $extension = null): array
    {
        if (! Storage::disk('backups')->exists('')) {
            return [];
        }

        $allFiles = Storage::disk('backups')->allFiles('');
        $backups = [];

        foreach ($allFiles as $file) {
            if ($extension) {
                if (str_ends_with($file, $extension)) {
                    $backups[$file] = $file;
                }
            } elseif (str_ends_with($file, '.dmp') || str_ends_with($file, '.sql')) {
                $backups[$file] = $file;
            }
        }

        return array_reverse($backups);
    }

    public function hasGlobalsSql(): bool
    {
        return Storage::disk('backups')->exists('globals.sql');
    }

    public function downloadAction(): Action
    {
        return Action::make('download')
            ->label('Download')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (array $arguments) {
                $file = $arguments['file'];
                return Storage::disk('backups')->download($file);
            });
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (array $arguments) {
                $file = $arguments['file'];
                Storage::disk('backups')->delete($file);

                Notification::make()
                    ->title('Backup deleted')
                    ->success()
                    ->send();
            });
    }

    public function backupAction(): Action
    {
        return Action::make('backup')
            ->label('Backup Now')
            ->color('success')
            ->icon('tabler-database-export')
            ->action(function () {
                $containerName = env('DOCKER_BACKUP_CONTAINER', 'mamias_db_backup');

                $result = Process::run("docker exec {$containerName} /backup-scripts/backups.sh");

                if ($result->successful()) {
                    Notification::make()
                        ->title('Backup started successfully')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Failed to trigger docker backup')
                        ->body($result->errorOutput() ?: 'Make sure the docker socket is shared or the app has permission to run docker commands.')
                        ->danger()
                        ->send();
                }
            });
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->label('Restore Database')
            ->color('danger')
            ->icon('tabler-database-import')
            ->schema([
                Select::make('backup_file')
                    ->label('Select Backup')
                    ->options($this->getBackupFiles())
                    ->required(),
            ])
            ->requiresConfirmation()
            ->modalHeading('Restore Database')
            ->modalDescription('Are you sure you want to restore the database? This will overwrite all current data.')
            ->action(function (array $data) {
                $file = $data['backup_file'];
                $dbContainer = env('DOCKER_DB_CONTAINER', 'mamias_db');
                $dbName = config('database.connections.pgsql.database');
                $dbUser = config('database.connections.pgsql.username');

                DB::disconnect();

                $dbPass = config('database.connections.pgsql.password');

                $terminateCmd = "PGPASSWORD='{$dbPass}' psql -h localhost -U {$dbUser} -d postgres -c \"SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$dbName}' AND pid <> pg_backend_pid();\"";
                $restoreCmd = "PGPASSWORD='{$dbPass}' pg_restore -h localhost -U {$dbUser} -d {$dbName} --clean --if-exists --no-owner --no-privileges /backups/{$file}";

                $result = Process::timeout(120)->run(
                    "docker exec {$dbContainer} bash -c '{$terminateCmd} && {$restoreCmd}'"
                );

                DB::reconnect();

                if ($result->successful() || $result->exitCode() === 1) {
                    Notification::make()
                        ->title('Restore completed successfully')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Restore failed')
                        ->body($result->errorOutput() ?: 'Error output was empty. Check container logs.')
                        ->danger()
                        ->send();
                }
            });
    }

    public function fullRestoreAction(): Action
    {
        return Action::make('fullRestore')
            ->label('Full Restore')
            ->color('danger')
            ->icon('heroicon-o-server-stack')
            ->schema([
                Select::make('backup_file')
                    ->label('Select Database Dump')
                    ->options($this->getBackupFiles('.dmp'))
                    ->required()
                    ->helperText('globals.sql will be applied automatically before the dump.'),
            ])
            ->requiresConfirmation()
            ->modalHeading('Full Disaster Recovery Restore')
            ->modalDescription('This will restore roles/users from globals.sql, then overwrite the database from the selected dump. Use this for blank server recovery.')
            ->action(function (array $data) {
                if (! $this->hasGlobalsSql()) {
                    Notification::make()
                        ->title('globals.sql not found')
                        ->body('Cannot perform full restore without globals.sql in the backups directory.')
                        ->danger()
                        ->send();

                    return;
                }

                $file = $data['backup_file'];
                $dbContainer = env('DOCKER_DB_CONTAINER', 'mamias_db');
                $dbName = config('database.connections.pgsql.database');
                $dbUser = config('database.connections.pgsql.username');
                $dbPass = config('database.connections.pgsql.password');

                DB::disconnect();

                $globalsCmd = "PGPASSWORD='{$dbPass}' psql -h localhost -U {$dbUser} -d postgres -f /backups/globals.sql";
                $terminateCmd = "PGPASSWORD='{$dbPass}' psql -h localhost -U {$dbUser} -d postgres -c \"SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$dbName}' AND pid <> pg_backend_pid();\"";
                $restoreCmd = "PGPASSWORD='{$dbPass}' pg_restore -h localhost -U {$dbUser} -d {$dbName} --clean --if-exists --no-owner --no-privileges /backups/{$file}";

                $result = Process::timeout(300)->run(
                    "docker exec {$dbContainer} bash -c '{$globalsCmd} && {$terminateCmd} && {$restoreCmd}'"
                );

                DB::reconnect();

                if ($result->successful() || $result->exitCode() === 1) {
                    Notification::make()
                        ->title('Full restore completed successfully')
                        ->body('Roles (globals.sql) and database dump have been restored.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Full restore failed')
                        ->body($result->errorOutput() ?: 'Error output was empty. Check container logs.')
                        ->danger()
                        ->send();
                }
            });
    }
}
