{{--
    Body of the status-badge pop-up (LiteraturesTable::getStatusColumn), shown
    in the panel and on the public "My references" page. Follows DESIGN-SYSTEM.md:
    zero radius, a 1px hairline instead of a shadow, the one red (Invasive /
    danger) only for a rejection, an eyebrow label, mono for what gets copied.

    Colours: the public site's theme-aware tokens (--status-*, --foreground,
    --muted-foreground) with the Filament ramp as fallback for the panel, which
    is light-only and does not load app.css. No `dark:` utilities — on the
    public build they follow the OS, not the site's html.dark switch.

    Class names are written out in full, never interpolated, so Tailwind finds
    them in both builds (panel theme.css and app.css).
--}}
@php
    $isRejected = $record->status === \App\Enums\LiteratureStatus::REJECTED;
@endphp

<div class="flex flex-col gap-4">
    <section
        @class([
            'border px-4 py-3',
            'border-[var(--status-invasive-border,var(--danger-200))] bg-[var(--status-invasive-fill,var(--danger-50))]' => $isRejected,
            'border-[var(--status-unresolved-border,var(--gray-200))] bg-[var(--status-unresolved-fill,var(--gray-50))]' => ! $isRejected,
        ])
    >
        <p
            @class([
                'text-[11px] leading-4 font-medium tracking-[.16em] uppercase',
                'text-[var(--status-invasive-text,var(--danger-600))]' => $isRejected,
                'text-[var(--status-unresolved-text,var(--gray-600))]' => ! $isRejected,
            ])
        >
            {{ $isRejected ? __('Rejection reason') : __('Reviewer comment') }}
        </p>
        <p class="mt-2 text-sm leading-[22px] whitespace-pre-line text-[var(--foreground,var(--gray-900))]">{{ $record->review_comment }}</p>
    </section>

    <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1 text-sm leading-[22px]">
        <dt class="text-[var(--muted-foreground,var(--gray-500))]">{{ __('Reference') }}</dt>
        <dd class="text-[var(--foreground,var(--gray-900))]">
            <span class="font-mono text-[13px] leading-5">{{ $record->code }}</span>
            <span class="text-[var(--muted-foreground,var(--gray-500))]">·</span>
            {{ $record->short_ref }}
        </dd>

        @if ($reviewerLabel ?? null)
            <dt class="text-[var(--muted-foreground,var(--gray-500))]">{{ __('Reviewed by') }}</dt>
            <dd class="text-[var(--foreground,var(--gray-900))]">{{ $reviewerLabel }}</dd>
        @endif

        @if ($record->reviewed_at)
            <dt class="text-[var(--muted-foreground,var(--gray-500))]">{{ __('Reviewed') }}</dt>
            <dd class="font-mono text-[13px] leading-5 text-[var(--foreground,var(--gray-900))]">{{ $record->reviewed_at->format('Y-m-d H:i') }}</dd>
        @endif
    </dl>
</div>
