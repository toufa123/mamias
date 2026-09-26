@php
    $user = auth()->user();
    $isCurator = $user?->hasAnyRole(['super_admin', 'scientist']);
    $toUpdate = $isCurator ? \App\Models\Taxon::whereNotNull('proposed_accepted_name')->count() : 0;
    $mine = $isCurator ? \App\Models\Taxon::whereNotNull('proposed_accepted_name')->where('name_reviewer_id', $user->id)->count() : 0;
    $url = \App\Filament\Resources\Taxons\TaxonResource::getUrl('index', ['tab' => 'rename']);
@endphp

@if ($toUpdate > 0)
    @include('filament-alert-box::alert-box', [
        'preview' => false,
        'config' => [
            'style' => 'info',
            'showIcon' => true,
            'title' => __('New accepted names in WoRMS'),
            'content' => '<p>'.trans_choice(':count species has a new accepted name in WoRMS.|:count species have a new accepted name in WoRMS.', $toUpdate)
                .($mine ? ' '.trans_choice(':count is waiting for your review.|:count are waiting for your review.', $mine) : '')
                .' <a href="'.$url.'" class="font-bold underline">'.__('Review them').'</a>.</p>',
        ],
    ])
@endif
