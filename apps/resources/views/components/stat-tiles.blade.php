@props(['stats'])

@php
    /**
     * Submission-state tiles for /my-suggestions and /my-species-reports.
     *
     * Submission states share the species status vocabulary by meaning:
     * pending is "awaiting review" (Unresolved), approved is "reviewed and
     * accepted" (Verified), rejected is the one red. The --status-* tokens in
     * app.css switch with the theme, so no dark: copies are needed.
     *
     * Class strings are written out in full because Tailwind scans this file as
     * plain text; an interpolated `text-[{{ $x }}]` would never be generated.
     */
    $tiles = [
        ['label' => 'Total', 'key' => 'total', 'tone' => 'text-[var(--foreground)]'],
        ['label' => 'Pending', 'key' => 'pending', 'tone' => 'text-[var(--status-unresolved-text)]'],
        ['label' => 'Approved', 'key' => 'approved', 'tone' => 'text-[var(--status-verified-text)]'],
        ['label' => 'Rejected', 'key' => 'rejected', 'tone' => 'text-[var(--status-invasive-text)]'],
    ];
@endphp

<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
    @foreach ($tiles as $tile)
        {{-- Hairline, no shadow: structure is a 1px rule and cards never lift (rule 2). --}}
        <div class="border border-border bg-[var(--card)] p-4">
            <p class="text-sm font-medium {{ $tile['tone'] }}">{{ $tile['label'] }}</p>
            <p class="mt-1 text-2xl font-semibold {{ $tile['tone'] }}">{{ $stats[$tile['key']] }}</p>
        </div>
    @endforeach
</div>
