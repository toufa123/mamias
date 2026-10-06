@php
    $pendingCount = auth()->user()?->hasAnyRole(['super_admin', 'scientist']) ? \App\Models\Occurrence::pendingCount() : 0;
@endphp

@if ($pendingCount > 0)
    @php
        $link = route('filament.mamias.resources.occurrences.index', ['filters' => ['status' => ['value' => \App\Enums\OccurrenceStatus::PENDING->value]]]);
        $body = '<p>'.($pendingCount === 1 ? __('There is 1 species occurrence pending review.') : __('There are :count species occurrences pending review.', ['count' => $pendingCount])).' <a href="'.$link.'" class="font-bold underline">'.__('Review them now').'</a>.</p>';
        // Stays until closed with ×. The count is in the id, so a new report brings a closed toast back.
        $id = 'banner.occurrences-pending.'.md5($body);

        if (! in_array($id, session('dismissed_banners', []))) {
            \Filament\Notifications\Notification::make($id)->warning()->persistent()->title(__('Pending Occurrences'))->body($body)->send();
        }
    @endphp
@endif
