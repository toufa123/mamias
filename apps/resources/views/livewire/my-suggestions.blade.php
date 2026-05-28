<div>
    @section('title', 'My Species Suggestions')

    @section('breadcrumbs')
        {{ Breadcrumbs::render('suggestions') }}
    @endsection

    <div class="space-y-8">
        <style>
            .stats-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; }
            @media (width >= 48rem) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (width >= 64rem) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }
        </style>
        <div class="stats-grid">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Pending</p>
                <p class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Approved</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $stats['approved'] }}</p>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm font-medium text-red-600 dark:text-red-400">Rejected</p>
                <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">{{ $stats['rejected'] }}</p>
            </div>
        </div>

        {{ $this->table }}
    </div>
</div>
