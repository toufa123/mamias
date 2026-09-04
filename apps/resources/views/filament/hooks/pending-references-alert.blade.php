@php
    $pendingCount = \App\Models\Literature::pending()->count();
@endphp

@if (auth()->user()?->hasAnyRole(['super_admin', 'scientist']) && $pendingCount > 0)
    @include('filament-alert-box::alert-box', [
        'preview' => false,
        'config' => [
            'style' => 'warning',
            'showIcon' => true,
            'title' => __('Pending References'),
            'content' => '<p>'.($pendingCount === 1 ? __('There is 1 bibliographic reference pending review.') : __('There are :count bibliographic references pending review.', ['count' => $pendingCount])).' <a href="'.route('filament.mamias.resources.literatures.index', ['tableFilters[status][value]' => 'pending']).'" class="font-bold underline text-warning-600 dark:text-warning-400">'.__('Review them now').'</a>.</p>',
        ],
    ])
@endif
