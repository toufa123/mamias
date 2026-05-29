<?php

namespace App\Filament\Resources\NisSuggestions\Pages;

use App\Filament\Resources\NisSuggestions\NisSuggestionResource;
use App\Models\NisSuggestion;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNisSuggestions extends ListRecords
{
    protected static string $resource = NisSuggestionResource::class;

    public function getTabs(): array
    {
        $trashedCount = NisSuggestion::onlyTrashed()->count();

        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(NisSuggestion::where('status', 'pending')->count())
                ->badgeColor('warning'),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            'trashed' => Tab::make('Trashed')
                ->icon('tabler-trash')
                ->badgeColor('danger')
                ->badge($trashedCount)
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }
}
