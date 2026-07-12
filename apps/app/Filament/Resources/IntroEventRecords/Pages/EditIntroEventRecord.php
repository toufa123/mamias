<?php

declare(strict_types=1);

namespace App\Filament\Resources\IntroEventRecords\Pages;

use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use Filament\Actions\DeleteAction;
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
