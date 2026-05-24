<?php

namespace App\Filament\Resources\Taxons\Schemas;

use App\Enums\Worms_Status;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Novadaemon\FilamentPrettyJson\Infolist\PrettyJsonEntry;

class TaxonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'md' => 1])
            ->components([
                self::getGeneralInformationSection(),
                Tabs::make('Taxon Details')
                    ->tabs([
                        self::getTaxonomicClassificationTab(),
                        self::getSynonymsTab(),
                    ])
                    ->columnSpanFull(),
                self::getStatusSection(),
                self::getAuditSection(),
            ]);
    }

    protected static function getGeneralInformationSection(): Section
    {
        return Section::make('General Information')
            ->description('Scientific name, authority, and primary identifiers.')
            ->icon('tabler-tag')
            ->columnSpanFull()
            ->schema([
                Grid::make(12)
                    ->schema([
                        TextEntry::make('scientificname')
                            ->label('Scientific Name')
                            ->icon('tabler-dna')
                            ->placeholder('—')
                            ->html()
                            ->formatStateUsing(fn ($state, $record) => trim(
                                '<div class="flex flex-col gap-1">
                                    <div class="text-xl font-extrabold italic tracking-tight text-primary-600 dark:text-primary-400">'.e((string) $state).'</div>
                                </div>'
                            ))
                            ->columnSpan(4),
                        TextEntry::make('url')
                            ->label('WoRMS URL')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->icon('tabler-external-link')
                            ->iconColor('primary')
                            ->color('primary')
                            ->formatStateUsing(fn ($state) => filled($state) ? 'View on WoRMS' : null)
                            ->placeholder('—')
                            ->columnSpan(4),
                        TextEntry::make('lsid')
                            ->label('LSID')
                            ->copyable()
                            ->icon('tabler-fingerprint')
                            ->placeholder('—')
                            ->url(fn ($state) => $state ? 'https://www.marinespecies.org/aphia.php?p=taxdetails&id='.last(explode(':', $state)) : null)
                            ->openUrlInNewTab()
                            ->columnSpan(4),
                        TextEntry::make('proposed_accepted_name')
                            ->label('Proposed Accepted Name')
                            ->icon('tabler-arrow-right-circle')
                            ->placeholder('—')
                            ->html()
                            ->formatStateUsing(fn ($state) => filled($state) ? "<em>{$state}</em>" : '—')
                            ->columnSpan(12),
                        TextEntry::make('authority')
                            ->label('Authority')
                            ->placeholder('—')
                            ->columnSpan(5),
                        TextEntry::make('aphia_id')
                            ->label('Aphia ID')
                            ->badge()
                            ->color('info')
                            ->icon('tabler-number')
                            ->placeholder('—')
                            ->url(fn ($state) => $state ? "https://www.marinespecies.org/aphia.php?p=taxdetails&id={$state}" : null)
                            ->openUrlInNewTab()
                            ->columnSpan(4),
                        TextEntry::make('is_extinct')
                            ->label('Extinct')
                            ->badge()
                            ->color('danger')
                            ->icon('tabler-skull')
                            ->formatStateUsing(fn ($state) => $state ? 'Extinct' : null)
                            ->visible(fn ($state) => (bool) $state)
                            ->columnSpan(3),
                    ]),
            ]);
    }

    protected static function getTaxonomicClassificationTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Taxonomic Classification')
            ->icon('tabler-school')
            ->schema([
                Grid::make(6)
                    ->schema([
                        TextEntry::make('kingdom')->label('Kingdom')->placeholder('—')->weight('bold'),
                        TextEntry::make('phylum')->label('Phylum')->placeholder('—')->weight('bold'),
                        TextEntry::make('class')->label('Class')->placeholder('—')->weight('bold'),
                        TextEntry::make('order')->label('Order')->placeholder('—')->weight('bold'),
                        TextEntry::make('family')->label('Family')->placeholder('—')->weight('bold'),
                        TextEntry::make('genus')->label('Genus')->placeholder('—')->weight('bold'),
                        TextEntry::make('rank')
                            ->label('Taxonomic Rank')
                            ->badge()
                            ->color('gray')
                            ->placeholder('—')
                            ->columnSpan(2),
                        TextEntry::make('environments')
                            ->label('Environment')
                            ->badge()
                            ->separator(',')
                            ->placeholder('—')
                            ->columnSpan(4),
                    ]),
            ]);
    }

    protected static function getStatusSection(): Section
    {
        return Section::make('Status & Validation')
            ->description('WoRMS/Catalogue status and notes.')
            ->icon('tabler-shield-check')
            ->collapsible()
            ->columnSpanFull()
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('worms_status')
                            ->label('WoRMS Status')
                            ->badge()
                            ->icon(fn ($state) => $state instanceof Worms_Status ? $state->getIcon() : 'tabler-info-circle')
                            ->placeholder('—'),
                        TextEntry::make('unacceptreason')
                            ->label('Unaccept Reason')
                            ->icon('tabler-alert-triangle')
                            ->placeholder('—')
                            ->columnSpan(2),
                        TextEntry::make('catalogue_status')
                            ->label('Catalogue Status')
                            ->badge()
                            ->placeholder('—')
                            ->columnSpan(1),
                        TextEntry::make('fetched_at')
                            ->label('Last Sync')
                            ->dateTime()
                            ->icon('tabler-refresh')
                            ->placeholder('—')
                            ->columnSpan(1),
                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime()
                            ->icon('tabler-calendar-event')
                            ->placeholder('—')
                            ->columnSpan(1),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->icon('tabler-note')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function getSynonymsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Synonyms')
            ->icon('tabler-list-details')
            ->schema([
                PrettyJsonEntry::make('synonyms_data')
                    ->hiddenLabel()
                    ->placeholder('No synonyms found in WoRMS.')
                    ->copyable(),
            ]);
    }

    protected static function getAuditSection(): Section
    {
        return Section::make('Audit')
            ->description('Authorship and timestamps.')
            ->icon('tabler-history')
            ->collapsible()
            ->collapsed()
            ->columnSpanFull()
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('creator')
                            ->label('Created By')
                            ->icon('tabler-user')
                            ->placeholder('—')
                            ->formatStateUsing(fn ($record) => self::formatUser($record?->creator)),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime()
                            ->icon('tabler-calendar-plus')
                            ->placeholder('—'),
                        TextEntry::make('Easin_id')
                            ->label('EASIN ID')
                            ->icon('tabler-id')
                            ->placeholder('—')
                            ->columnSpan(1),
                        TextEntry::make('editor')
                            ->label('Updated By')
                            ->icon('tabler-user-edit')
                            ->placeholder('—')
                            ->formatStateUsing(fn ($record) => self::formatUser($record?->editor)),
                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime()
                            ->icon('tabler-calendar-event')
                            ->placeholder('—'),
                    ]),
            ]);
    }

    protected static function formatUser($user): ?string
    {
        if (! $user) {
            return null;
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($name === '') {
            $name = (string) ($user->name ?? $user->email ?? '');
        }

        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->implode(', ') : '';

        return $roles !== '' ? trim($name).' ('.$roles.')' : trim($name);
    }
}
