<?php

declare(strict_types=1);

namespace App\Filament\Resources\IntroEventRecords\Pages;

use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Models\IntroEventRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Page for editing intro event records.
 */
class EditIntroEventRecord extends EditRecord
{
    protected static string $resource = IntroEventRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make()
                ->disabled(fn (IntroEventRecord $record): bool => $record->hasOccurrences())
                ->tooltip(fn (IntroEventRecord $record): ?string => $record->hasOccurrences()
                    ? 'Has occurrences. Delete or reassign them first.'
                    : null),
            RestoreAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Event Details';
    }

    public function getContentTabIcon(): string|\BackedEnum|Htmlable|null
    {
        return 'tabler-forms';
    }
}
