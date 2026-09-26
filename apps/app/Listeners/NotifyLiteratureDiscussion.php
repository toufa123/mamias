<?php

namespace App\Listeners;

use App\Models\Literature;
use App\Models\User;
use App\Notifications\LiteratureCommented;
use Illuminate\Support\Facades\Notification;
use Kirschbaum\Commentions\Events\CommentWasCreatedEvent;

/**
 * Routes a new comment on a reference to the other side of the conversation:
 * a submitter's comment to the moderator who reviewed it (or every moderator
 * while it is unreviewed), a moderator's comment to the submitter, and to
 * everyone following the discussion (its Participants).
 *
 * Registered by listener discovery — do not also add it in AppServiceProvider,
 * or it runs twice.
 */
class NotifyLiteratureDiscussion
{
    public function handle(CommentWasCreatedEvent $event): void
    {
        $literature = $event->comment->commentable;

        if (! $literature instanceof Literature) {
            return;
        }

        $author = $event->comment->author;
        $submitter = $literature->creator;

        $recipients = $submitter && $author?->is($submitter)
            ? ($literature->reviewer ? collect([$literature->reviewer]) : Literature::moderators())
            : collect([$submitter]);

        // Plus whoever follows the discussion (DiscussionParticipantsAction):
        // one notification each, however they came to be notified.
        $recipients = $recipients
            ->merge($literature->getSubscribers())
            ->filter()
            ->unique(fn (User $user): int => $user->getKey())
            ->reject(fn (User $user): bool => $user->is($author));

        Notification::send($recipients, new LiteratureCommented($event->comment));
    }
}
