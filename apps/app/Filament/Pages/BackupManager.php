<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BackupManager extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'tabler-database';

    protected string $view = 'filament.pages.backup-manager';

    protected static string|null|\UnitEnum $navigationGroup = 'System';

    protected static ?string $title = 'Backup Manager';

    protected function backupsPath(): string
    {
        return base_path('../backups');
    }

    protected function dbContainer(): string
    {
        $container = env('DOCKER_DB_CONTAINER', 'mamias_db');

        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/', $container)) {
            throw new \RuntimeException('Invalid DOCKER_DB_CONTAINER value.');
        }

        return $container;
    }

    protected function backupContainer(): string
    {
        $container = env('DOCKER_BACKUP_CONTAINER', 'mamias_db_backup');

        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/', $container)) {
            throw new \RuntimeException('Invalid DOCKER_BACKUP_CONTAINER value.');
        }

        return $container;
    }

    protected function dbCredentials(): array
    {
        return [
            'name' => config('database.connections.pgsql.database'),
            'user' => config('database.connections.pgsql.username'),
            'pass' => config('database.connections.pgsql.password'),
        ];
    }

    /**
     * List .dmp files from the locally mounted backups directory.
     */
    public function getBackupFiles(): array
    {
        $path = $this->backupsPath();

        if (! is_dir($path)) {
            return [];
        }

        $files = collect(File::allFiles($path))
            ->filter(fn ($file) => $file->getExtension() === 'dmp')
            ->map(fn ($file) => $file->getPathname())
            ->all();
        rsort($files);

        $options = [];

        foreach ($files as $file) {
            $options[$file] = basename($file);
        }

        return $options;
    }

    public function hasGlobalsSql(): bool
    {
        return file_exists($this->backupsPath().'/globals.sql');
    }

    public function getBackupFilesWithSize(): array
    {
        $path = $this->backupsPath();

        if (! is_dir($path)) {
            return [];
        }

        $files = collect(File::allFiles($path))
            ->filter(fn ($file) => $file->getExtension() === 'dmp')
            ->map(fn ($file) => $file->getPathname())
            ->all();
        $result = [];

        foreach ($files as $file) {
            $result[] = [
                'path' => $file,
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => date('Y-m-d H:i', filemtime($file)),
            ];
        }

        usort($result, fn ($a, $b) => filemtime($b['path']) <=> filemtime($a['path']));

        return $result;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Copy a backup file into the db container for restore.
     */
    protected function copyToDbContainer(string $localPath, string $destPath = '/tmp/_mamias_restore.dump'): bool
    {
        if (! file_exists($localPath)) {
            return false;
        }

        $result = Process::timeout(60)->run(
            sprintf(
                'docker exec -i %s tee %s > /dev/null < %s',
                escapeshellarg($this->dbContainer()),
                escapeshellarg($destPath),
                escapeshellarg($localPath)
            )
        );

        return $result->successful();
    }

    public function downloadAction(): Action
    {
        return Action::make('download')
            ->label('Download')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (array $arguments) {
                $filePath = $arguments['file'];
                $basePath = $this->backupsPath();

                $realPath = realpath($filePath);
                if ($realPath === false || ! str_starts_with($realPath, realpath($basePath)) || ! str_ends_with($realPath, '.dmp')) {
                    Notification::make()->title('Download failed')->body('Invalid file path.')->danger()->send();

                    return null;
                }

                $basename = basename($filePath);

                if (! file_exists($filePath)) {
                    Notification::make()->title('Download failed')->body('File not found.')->danger()->send();

                    return null;
                }

                return response()->download($filePath, $basename);
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
                $filePath = $arguments['file'];
                $basePath = $this->backupsPath();

                $realPath = realpath($filePath);
                if ($realPath === false || ! str_starts_with($realPath, realpath($basePath)) || ! str_ends_with($realPath, '.dmp')) {
                    Notification::make()->title('Delete failed')->body('Invalid file path.')->danger()->send();

                    return;
                }

                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                $metaPath = $filePath.'.meta.json';
                if (file_exists($metaPath)) {
                    unlink($metaPath);
                }

                Notification::make()->title('Backup deleted')->success()->send();
            });
    }

    public function backupAction(): Action
    {
        return Action::make('backup')
            ->label('Backup Now')
            ->color('success')
            ->icon('tabler-database-export')
            ->action(function () {
                $result = Process::timeout(120)->run(
                    sprintf('docker exec %s /backup-scripts/backups.sh', escapeshellarg($this->backupContainer()))
                );

                if ($result->successful()) {
                    Notification::make()
                        ->title('Backup completed')
                        ->body('Database dump created successfully.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Backup failed')
                        ->body($result->errorOutput() ?: 'Check backup container logs.')
                        ->danger()
                        ->send();
                }
            });
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->label('Restore (Data Only)')
            ->color('warning')
            ->icon('tabler-database-import')
            ->schema([
                Select::make('backup_file')
                    ->label('Select Backup')
                    ->options($this->getBackupFiles())
                    ->required()
                    ->helperText('Reloads data into existing tables. Schema is preserved.'),
            ])
            ->requiresConfirmation()
            ->modalHeading('Restore Database Data')
            ->modalDescription('This will truncate all tables and reload data from the selected backup. Table structure and indexes are preserved.')
            ->action(function (array $data) {
                $file = $data['backup_file'];
                $basePath = $this->backupsPath();

                $realPath = realpath($file);
                if ($realPath === false || ! str_starts_with($realPath, realpath($basePath)) || ! str_ends_with($realPath, '.dmp')) {
                    Notification::make()->title('Restore failed')->body('Invalid file path.')->danger()->send();

                    return;
                }

                $db = $this->dbCredentials();
                $dbContainer = $this->dbContainer();

                if (! $this->copyToDbContainer($realPath)) {
                    Notification::make()->title('Restore failed')->body('Could not copy dump file to database container.')->danger()->send();

                    return;
                }

                DB::disconnect();

                $truncateSql = "DO \$\$ DECLARE r RECORD; BEGIN FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename NOT IN ('spatial_ref_sys')) LOOP EXECUTE 'TRUNCATE TABLE public.' || quote_ident(r.tablename) || ' CASCADE'; END LOOP; END \$\$;";

                $truncateCmd = sprintf(
                    'docker exec -e PGPASSWORD=%s %s psql -h localhost -U %s -d %s -q -c %s',
                    escapeshellarg($db['pass']),
                    escapeshellarg($dbContainer),
                    escapeshellarg($db['user']),
                    escapeshellarg($db['name']),
                    escapeshellarg($truncateSql)
                );

                $restoreCmd = sprintf(
                    'docker exec -e PGPASSWORD=%s %s pg_restore -h localhost -U %s -d %s --data-only --disable-triggers --no-owner --no-privileges --single-transaction /tmp/_mamias_restore.dump',
                    escapeshellarg($db['pass']),
                    escapeshellarg($dbContainer),
                    escapeshellarg($db['user']),
                    escapeshellarg($db['name'])
                );

                $cleanupCmd = sprintf('docker exec %s rm -f /tmp/_mamias_restore.dump', escapeshellarg($dbContainer));

                $result = Process::timeout(120)->run("{$truncateCmd} && {$restoreCmd}; {$cleanupCmd}");

                DB::reconnect();

                if ($result->successful() || $result->exitCode() === 1) {
                    Notification::make()
                        ->title('Data restore completed')
                        ->body('All data reloaded. Schema was preserved.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Restore failed')
                        ->body($result->errorOutput() ?: 'Check container logs for details.')
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
                    ->options($this->getBackupFiles())
                    ->required()
                    ->helperText('globals.sql will be applied automatically before the dump if available.'),
            ])
            ->requiresConfirmation()
            ->modalHeading('Full Disaster Recovery Restore')
            ->modalDescription('This will DROP and RECREATE all tables from the selected dump. Use this for blank server recovery or when schema has changed.')
            ->action(function (array $data) {
                $file = $data['backup_file'];
                $basePath = $this->backupsPath();

                $realPath = realpath($file);
                if ($realPath === false || ! str_starts_with($realPath, realpath($basePath)) || ! str_ends_with($realPath, '.dmp')) {
                    Notification::make()->title('Restore failed')->body('Invalid file path.')->danger()->send();

                    return;
                }

                $db = $this->dbCredentials();
                $dbContainer = $this->dbContainer();

                if (! $this->copyToDbContainer($realPath)) {
                    Notification::make()->title('Restore failed')->body('Could not copy dump file to database container.')->danger()->send();

                    return;
                }

                DB::disconnect();

                $commands = [];

                if ($this->hasGlobalsSql()) {
                    $globalsPath = $this->backupsPath().'/globals.sql';
                    $this->copyToDbContainer($globalsPath, '/tmp/_mamias_globals.sql');
                    $commands[] = sprintf(
                        'docker exec -e PGPASSWORD=%s %s psql -h localhost -U %s -d postgres -q -f /tmp/_mamias_globals.sql',
                        escapeshellarg($db['pass']),
                        escapeshellarg($dbContainer),
                        escapeshellarg($db['user'])
                    );
                }

                $commands[] = sprintf(
                    'docker exec -e PGPASSWORD=%s %s psql -h localhost -U %s -d postgres -c %s',
                    escapeshellarg($db['pass']),
                    escapeshellarg($dbContainer),
                    escapeshellarg($db['user']),
                    escapeshellarg("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$db['name']}' AND pid <> pg_backend_pid();")
                );

                $commands[] = sprintf(
                    'docker exec -e PGPASSWORD=%s %s pg_restore -h localhost -U %s -d %s --clean --if-exists --no-owner --no-privileges --single-transaction /tmp/_mamias_restore.dump',
                    escapeshellarg($db['pass']),
                    escapeshellarg($dbContainer),
                    escapeshellarg($db['user']),
                    escapeshellarg($db['name'])
                );

                $commands[] = sprintf('docker exec %s rm -f /tmp/_mamias_restore.dump /tmp/_mamias_globals.sql', escapeshellarg($dbContainer));

                $fullCmd = implode(' && ', $commands);
                $result = Process::timeout(300)->run($fullCmd.' || true');

                DB::reconnect();

                if ($result->successful() || $result->exitCode() === 1) {
                    Notification::make()
                        ->title('Full restore completed')
                        ->body('Database fully restored from dump.')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Restore failed')
                        ->body($result->errorOutput() ?: 'Check container logs for details.')
                        ->danger()
                        ->send();
                }
            });
    }
}
