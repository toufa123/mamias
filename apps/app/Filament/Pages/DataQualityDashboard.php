<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\DataQuality\CompletenessChartWidget;
use App\Filament\Widgets\DataQuality\QualityStatsWidget;
use App\Services\DataQualityService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DataQualityDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-clipboard-check';

    protected string $view = 'filament.pages.data-quality-dashboard';

    protected static string|null|\UnitEnum $navigationGroup = 'Quality';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Data Quality Dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function table(Table $table): Table
    {
        $service = app(DataQualityService::class);

        return $table
            ->query($service->getIssuesQuery())
            ->columns([
                TextColumn::make('entity_type')
                    ->label('Entity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Taxon' => 'primary',
                        'Occurrence' => 'success',
                        'NisSuggestion' => 'warning',
                        'IntroEventRecord' => 'info',
                        default => 'gray',
                    })
                    ->width(130),
                TextColumn::make('id')
                    ->label('Record ID')
                    ->width(90),
                TextColumn::make('issue_type')
                    ->label('Issue')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending_moderation' => 'warning',
                        'needs_review' => 'danger',
                        'stale_worms' => 'info',
                        'non_accepted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->width(150),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'info',
                        default => 'gray',
                    })
                    ->width(90),
                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->extraAttributes(['class' => 'max-w-lg']),
                TextColumn::make('created_at')
                    ->label('Detected')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->width(140),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('entity_type')
                    ->label('Entity')
                    ->options([
                        'Taxon' => 'Taxon',
                        'Occurrence' => 'Occurrence',
                        'NisSuggestion' => 'NIS Suggestion',
                        'IntroEventRecord' => 'Intro Event Record',
                    ]),
                SelectFilter::make('issue_type')
                    ->label('Issue Type')
                    ->options([
                        'pending_moderation' => 'Pending Moderation',
                        'needs_review' => 'Needs Review',
                        'stale_worms' => 'Stale WoRMS Sync',
                        'non_accepted' => 'Non-Accepted Taxon',
                    ]),
                SelectFilter::make('severity')
                    ->label('Severity')
                    ->options([
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),
            ])
            ->filtersFormWidth(Width::ExtraLarge)
            ->poll('30s');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QualityStatsWidget::class,
            CompletenessChartWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('tabler-refresh')
                ->color('gray')
                ->action(function () {
                    app(DataQualityService::class)->clearCache();
                    $this->dispatch('$refresh');
                }),
            Action::make('export')
                ->label('Export CSV')
                ->icon('tabler-download')
                ->color('gray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    protected function exportCsv(): void
    {
        $service = app(DataQualityService::class);
        $issues = $service->getIssuesQuery()->get();

        $csv = "Entity,Record ID,Issue Type,Severity,Description,Detected\n";

        foreach ($issues as $issue) {
            $csv .= sprintf(
                "%s,%d,%s,%s,%s,%s\n",
                $issue->entity_type,
                $issue->id,
                $issue->issue_type,
                $issue->severity,
                str_replace('"', '""', $issue->description ?? ''),
                $issue->created_at ?? '',
            );
        }

        $filename = 'data-quality-export-'.now()->format('Y-m-d-His').'.csv';

        response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename)->send();

        exit;
    }
}
