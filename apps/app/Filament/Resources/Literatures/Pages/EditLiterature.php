<?php

namespace App\Filament\Resources\Literatures\Pages;

use App\Filament\Actions\DiscussionParticipantsAction;
use App\Filament\Resources\Literatures\LiteratureGuide;
use App\Filament\Resources\Literatures\LiteratureResource;
use App\Filament\Resources\Literatures\Tables\LiteraturesTable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Kirschbaum\Commentions\Filament\Actions\CommentsAction;

/**
 * Page for editing literatures.
 */
class EditLiterature extends EditRecord
{
    protected static string $resource = LiteratureResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    /**
     * The code is not a form field: it is assigned on save and never edited.
     */
    public function getSubheading(): string|Htmlable|null
    {
        return $this->getRecord()->code;
    }

    protected function getHeaderActions(): array
    {
        return [
            LiteraturesTable::getApproveAction(),
            LiteraturesTable::getRejectAction(),
            CommentsAction::make()
                ->label('Discussion')
                ->color('gray')
                ->modalDescription(fn (): HtmlString => DiscussionParticipantsAction::summary($this->getRecord()))
                ->disableSidebar(),
            DiscussionParticipantsAction::make(),
            LiteraturesTable::getBibtexAction(),
            LiteraturesTable::getMergeAction(),
            // Deleting a cited reference would cascade to its introduction
            // events; those are merged into another reference instead.
            DeleteAction::make()
                ->hidden(fn (): bool => $this->getRecord()->citationCount() > 0),
            LiteratureGuide::action(),
        ];
    }
}
