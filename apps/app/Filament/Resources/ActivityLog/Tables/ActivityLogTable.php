<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLog\Tables;

use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->width(140),
                TextColumn::make('description')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created', 'approved' => 'success',
                        'updated' => 'info',
                        'deleted', 'rejected', 'role_removed' => 'danger',
                        'restored' => 'warning',
                        'role_assigned' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->width(130),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->width(160),
                TextColumn::make('subject_type')
                    ->label('Record Type')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->searchable()
                    ->width(140),
                TextColumn::make('subject_id')
                    ->label('Record ID')
                    ->width(80),
                TextColumn::make('event')
                    ->label('Event type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    })
                    ->width(100),
                TextColumn::make('properties')
                    ->label('Details')
                    ->formatStateUsing(function (?array $state): string {
                        if (empty($state)) {
                            return '-';
                        }

                        $lines = [];

                        if (isset($state['attributes'])) {
                            $changes = collect($state['attributes'])
                                ->take(5)
                                ->map(fn ($value, $key) => "{$key}: {$value}")
                                ->implode(', ');

                            $lines[] = $changes;

                            if (count($state['attributes']) > 5) {
                                $lines[] = '… and more';
                            }
                        }

                        if (isset($state['roles'])) {
                            $roles = is_array($state['roles']) ? implode(', ', $state['roles']) : (string) $state['roles'];
                            $lines[] = "Roles: {$roles}";
                        }

                        if (isset($state['old_status'])) {
                            $lines[] = "{$state['old_status']} → {$state['new_status']}";
                        }

                        if (isset($state['rejection_reason'])) {
                            $reason = Str::limit($state['rejection_reason'], 80);
                            $lines[] = "Reason: {$reason}";
                        }

                        if (isset($state['scientificname'])) {
                            $lines[] = "Species: {$state['scientificname']}";
                        }

                        return implode(' | ', $lines);
                    })
                    ->wrap()
                    ->extraAttributes(['class' => 'max-w-md']),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label('Event type')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'role_assigned' => 'Role Assigned',
                        'role_removed' => 'Role Removed',
                    ]),
                SelectFilter::make('log_name')
                    ->label('Log')
                    ->options([
                        'default' => 'Default',
                    ]),
                Filter::make('created_at')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn (Builder $q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn (Builder $q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),
            ])
            ->filtersFormWidth(Width::ExtraLarge)
            ->poll('30s');
    }
}
