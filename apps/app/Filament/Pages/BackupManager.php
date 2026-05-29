<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-database';

    protected string $view = 'filament.pages.backup-manager';

    protected static string|null|\UnitEnum $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Backup Manager';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

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

    public function getBackupFilesWithSize(?string $sortColumn = null, ?string $sortDirection = null, ?array $filters = null): array
    {
        $files = Storage::disk('backups')->allFiles('');

        $backups = [];

        foreach ($files as $file) {
            if (! str_ends_with($file, '.dmp') && ! str_ends_with($file, '.sql')) {
                continue;
            }

            $size = Storage::disk('backups')->size($file);
            $lastModified = Storage::disk('backups')->lastModified($file);
            $lastModifiedDate = now()->createFromTimestamp($lastModified);

            if (isset($filters['date'])) {
                $filter = $filters['date'];

                if (filled($filter['date_from'] ?? null) && $lastModifiedDate->startOfDay()->lt(now()->parse($filter['date_from'])->startOfDay())) {
                    continue;
                }

                if (filled($filter['date_until'] ?? null) && $lastModifiedDate->startOfDay()->gt(now()->parse($filter['date_until'])->startOfDay())) {
                    continue;
                }
            }

            $backups[] = [
                '__key' => $file,
                'name' => $file,
                'size' => $this->formatBytes($size),
                'size_bytes' => $size,
                'date' => $lastModifiedDate->format('Y-m-d H:i'),
                'last_modified' => $lastModified,
                'path' => $file,
            ];
        }

        if ($sortColumn && in_array($sortColumn, ['name', 'size', 'date'], true)) {
            $sortField = match ($sortColumn) {
                'size' => 'size_bytes',
                'date' => 'last_modified',
                default => $sortColumn,
            };

            usort($backups, function (array $a, array $b) use ($sortField, $sortDirection) {
                $cmp = in_array($sortField, ['size_bytes', 'last_modified'], true)
                    ? $a[$sortField] <=> $b[$sortField]
                    : strcasecmp((string) $a[$sortField], (string) $b[$sortField]);

                return ($sortDirection ?? 'asc') === 'asc' ? $cmp : -$cmp;
            });
        } else {
            usort($backups, fn (array $a, array $b) => $b['path'] <=> $a['path']);
        }

        return $backups;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return match (true) {
            $i === 0 => "{$bytes} {$units[$i]}",
            default => round($bytes, 1)." {$units[$i]}",
        };
    }

    public function hasGlobalsSql(): bool
    {
        return Storage::disk('backups')->exists('globals.sql');
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $sortColumn, ?string $sortDirection, ?array $filters): array => $this->getBackupFilesWithSize(
                sortColumn: $sortColumn,
                sortDirection: $sortDirection,
                filters: $filters,
            ))
            ->columns([
                TextColumn::make('name')
                    ->label('Filename')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->sortable(),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (array $record) => Storage::disk('backups')->download($record['path'])),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (array $record) {
                        Storage::disk('backups')->delete($record['path']);

                        Notification::make()
                            ->title('Backup deleted')
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('date_from')->label('From'),
                        DatePicker::make('date_until')->label('Until'),
                    ]),
            ])
            ->headerActions([
                $this->backupAction(),
                $this->restoreAction(),
                $this->fullRestoreAction(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('deleteSelected')
                        ->label('Delete selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                Storage::disk('backups')->delete($record['path']);
                            }

                            Notification::make()
                                ->title('Selected backups deleted')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('last_modified', 'desc')
            ->poll('60s');
    }

    public function backupAction(): Action
    {
        return Action::make('backup')
            ->label('Backup Now')
            ->tooltip('Create a new database backup dump via the backup container')
            ->color('success')
            ->icon('tabler-database-export')
            ->action(function () {
                $result = Process::timeout(300)->run([
                    'docker', 'exec', config('backup.backup_container'), '/backup-scripts/backups.sh',
                ]);

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
            ->tooltip('Restore the database from a selected backup dump file')
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
                $file = $this->validateBackupFile($data['backup_file']);
                $container = config('backup.db_container');
                $dbName = config('database.connections.pgsql.database');
                $dbUser = config('database.connections.pgsql.username');
                $dbPass = config('database.connections.pgsql.password');

                DB::disconnect();

                $this->terminateDbConnections($container, $dbName, $dbUser, $dbPass);

                $result = $this->runPgRestore($container, $dbName, $dbUser, $dbPass, $file, 120);

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
            ->tooltip('Disaster recovery — restores globals.sql then the database dump')
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

                $file = $this->validateBackupFile($data['backup_file']);
                $container = config('backup.db_container');
                $dbName = config('database.connections.pgsql.database');
                $dbUser = config('database.connections.pgsql.username');
                $dbPass = config('database.connections.pgsql.password');

                DB::disconnect();

                $this->runPsqlFile($container, $dbUser, $dbPass, '/backups/globals.sql');
                $this->terminateDbConnections($container, $dbName, $dbUser, $dbPass);
                $result = $this->runPgRestore($container, $dbName, $dbUser, $dbPass, $file, 300);

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

    private function validateBackupFile(string $file): string
    {
        if (str_contains($file, '..') || str_contains($file, '/')) {
            throw new \InvalidArgumentException('Invalid backup file name.');
        }

        return $file;
    }

    private function terminateDbConnections(string $container, string $dbName, string $dbUser, string $dbPass): void
    {
        $safeDbName = str_replace("'", "''", $dbName);

        Process::timeout(30)->run([
            'docker', 'exec', '-e', "PGPASSWORD={$dbPass}", $container,
            'psql', '-h', 'localhost', '-U', $dbUser, '-d', 'postgres',
            '-c', "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '{$safeDbName}' AND pid <> pg_backend_pid();",
        ]);
    }

    private function runPsqlFile(string $container, string $dbUser, string $dbPass, string $file): void
    {
        Process::timeout(60)->run([
            'docker', 'exec', '-e', "PGPASSWORD={$dbPass}", $container,
            'psql', '-h', 'localhost', '-U', $dbUser, '-d', 'postgres', '-f', $file,
        ]);
    }

    private function runPgRestore(string $container, string $dbName, string $dbUser, string $dbPass, string $file, int $timeout): ProcessResult
    {
        return Process::timeout($timeout)->run([
            'docker', 'exec', '-e', "PGPASSWORD={$dbPass}", $container,
            'pg_restore', '-h', 'localhost', '-U', $dbUser, '-d', $dbName,
            '--clean', '--if-exists', '--no-owner', '--no-privileges', "/backups/{$file}",
        ]);
    }
}
