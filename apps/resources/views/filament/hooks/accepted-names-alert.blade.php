@php
    $user = auth()->user();
    $isCurator = $user?->hasAnyRole(['super_admin', 'scientist']);
    $toUpdate = $isCurator ? \App\Models\Taxon::whereNotNull('proposed_accepted_name')->count() : 0;
    $mine = $isCurator ? \App\Models\Taxon::whereNotNull('proposed_accepted_name')->where('name_reviewer_id', $user->id)->count() : 0;
    $url = \App\Filament\Resources\Taxons\TaxonResource::getUrl('index', ['tab' => 'rename']);

    if ($toUpdate > 0) {
        $body = '<p>'.trans_choice(':count species has a new accepted name in WoRMS.|:count species have a new accepted name in WoRMS.', $toUpdate)
            .($mine ? ' '.trans_choice(':count is waiting for your review.|:count are waiting for your review.', $mine) : '')
            .' <a href="'.$url.'" class="font-bold underline">'.__('Review them').'</a>.</p>';
        $id = 'banner.accepted-names.'.md5($body);

        // Landing on the toast's link counts as dismissing it.
        if (url()->full() === $url) {
            session()->push('dismissed_banners', $id);
        }

        if (! in_array($id, session('dismissed_banners', []))) {
            \Filament\Notifications\Notification::make($id)->info()->persistent()->title(__('New accepted names in WoRMS'))->body($body)->send();
        }
    }
@endphp
