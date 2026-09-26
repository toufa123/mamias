<?php

namespace App\Listeners;

use App\Filament\Resources\Taxons\TaxonResource;
use App\Models\Taxon;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Kirschbaum\Commentions\Events\UserIsSubscribedToCommentableEvent;

/**
 * Tells each participant of a species discussion about a new message, in
 * the panel. Commentions dispatches the event once per subscriber, author
 * excluded. Literature discussions route their own notifications
 * (NotifyLiteratureDiscussion).
 *
 * Registered by listener discovery.
 */
class NotifyTaxonDiscussion
{
    public function handle(UserIsSubscribedToCommentableEvent $event): void
    {
        $taxon = $event->comment->commentable;

        if (! $taxon instanceof Taxon || ! $event->user instanceof User) {
            return;
        }

        Notification::make()
            ->title("New message on {$taxon->scientificname}")
            ->body(($event->comment->author?->name ?? 'Someone').': '.Str::limit(strip_tags($event->comment->body), 120))
            ->icon('tabler-message-circle')
            ->actions([
                Action::make('open')
                    ->label('Open the species')
                    ->url(TaxonResource::getUrl('edit', ['record' => $taxon])),
            ])
            ->sendToDatabase($event->user);
    }
}
