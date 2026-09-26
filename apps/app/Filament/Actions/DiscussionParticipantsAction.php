<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Kirschbaum\Commentions\Contracts\Commentable;
use Zvizvi\UserFields\Components\UserSelect;

/**
 * Chooses who follows a record's discussion: the Commentions subscribers,
 * who are notified of every new message (NotifyTaxonDiscussion,
 * NotifyLiteratureDiscussion). Anyone who writes joins automatically
 * (commentions.subscriptions.auto_subscribe_on_comment).
 */
class DiscussionParticipantsAction
{
    public static function make(): Action
    {
        return Action::make('discussion_participants')
            ->label('Participants')
            ->icon('tabler-users')
            ->color('gray')
            ->modalHeading('Who follows this discussion')
            ->modalDescription('Participants are notified of every new message. Anyone who writes in the discussion joins automatically.')
            ->fillForm(fn (Commentable&Model $record): array => ['participants' => $record->getSubscribers()->modelKeys()])
            ->schema(fn (Commentable&Model $record): array => [
                UserSelect::make('participants')
                    ->hiddenLabel()
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => self::userOptions(self::candidates($record))),
            ])
            ->modalSubmitActionLabel('Save')
            ->action(function (Commentable&Model $record, array $data): void {
                $chosen = User::whereKey($data['participants'] ?? [])->get();
                $current = $record->getSubscribers();

                $chosen->reject(fn (User $user): bool => $current->contains($user))->each(fn (User $user) => $record->subscribe($user));
                $current->reject(fn ($user): bool => $chosen->contains($user))->each(fn ($user) => $record->unsubscribe($user));

                Notification::make()
                    ->title(trans_choice(':count participant|:count participants', $chosen->count()))
                    ->success()
                    ->send();
            });
    }

    /**
     * The participants as overlapping avatars, names on hover, for the top
     * of a Discussion window. Inline styles: the panel theme is not built
     * from this file, so utility classes here could be purged.
     */
    public static function summary(Commentable&Model $record): HtmlString
    {
        $participants = $record->getSubscribers()->sortBy('name')->values();

        if ($participants->isEmpty()) {
            return new HtmlString('<span>No participants yet. Add some with <strong>Participants</strong>, or write below to join.</span>');
        }

        $avatars = $participants
            ->map(fn (User $user, int $index): string => Blade::render(
                '<span title="{{ $name }}" style="display:inline-flex;border-radius:9999px;box-shadow:0 0 0 2px #fff;margin-left:{{ $offset }}"><x-filament-panels::avatar.user :user="$user" size="md" /></span>',
                ['user' => $user, 'name' => $user->name, 'offset' => $index ? '-0.5rem' : '0'],
            ))
            ->implode('');

        return new HtmlString(
            '<span style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap">'
            .'<span style="display:inline-flex">'.$avatars.'</span>'
            .'<span>'.e($participants->pluck('name')->join(', ', ' and ')).' '.($participants->count() === 1 ? 'follows' : 'follow').' this discussion.</span>'
            .'</span>'
        );
    }

    /**
     * Users as avatar-and-name labels for a UserSelect built from options
     * rather than a relationship.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, string>
     */
    public static function userOptions(Collection $users): array
    {
        return $users
            ->sortBy('name')
            ->mapWithKeys(fn (User $user): array => [$user->getKey() => view('user-fields::user-avatar-option', ['user' => $user])->render()])
            ->all();
    }

    /**
     * Who can be added: the panel team, whoever already follows, and the
     * record's submitter (a contributor on the public site for references).
     *
     * @return Collection<int, User>
     */
    private static function candidates(Commentable&Model $record): Collection
    {
        return User::whereHas('roles', fn (Builder $query) => $query->whereIn('name', ['super_admin', 'scientist']))
            ->get()
            ->merge($record->getSubscribers())
            ->when($record->creator ?? null, fn (Collection $users, User $creator) => $users->push($creator))
            ->unique('id');
    }
}
