<?php

declare(strict_types=1);

namespace App\Filament\Resources\IntroEventRecords\Pages;

use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use App\Models\IntroEventRecord;
use Blendbyte\FilamentResourceLock\Resources\Pages\Concerns\UsesResourceLock;
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
    use UsesResourceLock;

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

    /**
     * Saving is a human having been through the record, which is what the
     * Needs review tab asks for, so the flag goes with it (as on promotion).
     * needs_review is not fillable, hence set on the record before update().
     */
    protected function beforeSave(): void
    {
        $this->record->needs_review = false;
    }

    /**
     * Back to the list the record was opened from, e.g. the Needs review tab.
     */
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
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
