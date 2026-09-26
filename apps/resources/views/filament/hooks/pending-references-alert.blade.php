@php
    $isModerator = auth()->user()?->hasAnyRole(['super_admin', 'scientist']);
    $pendingCount = $isModerator ? \App\Models\Literature::pendingCount() : 0;
    // ponytail: capped at 10 links, a list filter if moderators ever fall that far behind.
    $unanswered = $isModerator ? \App\Models\Literature::withUnansweredComments(fromSubmitter: true)->latest('updated_at')->limit(10)->get(['id', 'code', 'short_ref']) : collect();
@endphp

@if ($unanswered->isNotEmpty())
    @include('filament-alert-box::alert-box', [
        'preview' => false,
        'config' => [
            'style' => 'info',
            'showIcon' => true,
            'title' => __('New comments on references'),
            'content' => '<p>'.__('Submitters are waiting for a reply on:').' '.$unanswered->map(fn ($reference) => '<a href="'.route('filament.mamias.resources.literatures.edit', $reference).'" class="font-bold underline">'.e("{$reference->code} — {$reference->short_ref}").'</a>')->join(', ').'.</p>',
        ],
    ])
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
