<?php

declare(strict_types=1);

namespace App\Filament\Resources\NisSuggestions\Pages;

use App\Filament\Resources\NisSuggestions\Actions\NisSuggestionActions;
use App\Filament\Resources\NisSuggestions\NisSuggestionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Page for viewing NIS suggestions.
 */
class ViewNisSuggestion extends ViewRecord
{
    protected static string $resource = NisSuggestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
            NisSuggestionActions::makeApproveAction(
                afterAction: fn () => $this->refreshFormData(['status', 'rejection_reason'])
            ),
            NisSuggestionActions::makeRejectAction(
                afterAction: fn () => $this->refreshFormData(['status', 'rejection_reason'])
            ),
        ];
    }
}
