@php
    $isModerator = auth()->user()?->hasAnyRole(['super_admin', 'scientist']);
    $pendingCount = $isModerator ? \App\Models\Literature::pendingCount() : 0;
    // ponytail: capped at 10 links, a list filter if moderators ever fall that far behind.
    $unanswered = $isModerator ? \App\Models\Literature::withUnansweredComments(fromSubmitter: true)->latest('updated_at')->limit(10)->get(['id', 'code', 'short_ref']) : collect();
@endphp

@if ($unanswered->isNotEmpty())
    @php
        $links = $unanswered->mapWithKeys(fn ($reference) => [route('filament.mamias.resources.literatures.edit', $reference) => "{$reference->code} — {$reference->short_ref}"]);
        $body = '<p>'.__('Submitters are waiting for a reply on:').' '.$links->map(fn ($label, $link) => '<a href="'.$link.'" class="font-bold underline">'.e($label).'</a>')->join(', ').'.</p>';
        $id = 'banner.reference-comments.'.md5($body);

        // Landing on any of the toast's links counts as dismissing it.
        if ($links->has(url()->current())) {
            session()->push('dismissed_banners', $id);
        }

        if (! in_array($id, session('dismissed_banners', []))) {
            \Filament\Notifications\Notification::make($id)->info()->persistent()->title(__('New comments on references'))->body($body)->send();
        }
    @endphp
@endif

@if ($pendingCount > 0)
    @include('filament-alert-box::alert-box', [
        'preview' => false,
        'config' => [
            'style' => 'warning',
            'showIcon' => true,
            'title' => __('Pending References'),
            'content' => '<p>'.($pendingCount === 1 ? __('There is 1 bibliographic reference pending review.') : __('There are :count bibliographic references pending review.', ['count' => $pendingCount])).' <a href="'.route('filament.mamias.resources.literatures.index', ['tab' => 'pending']).'" class="font-bold underline text-warning-600 dark:text-warning-400">'.__('Review them now').'</a>.</p>',
        ],
    ])
@endif
