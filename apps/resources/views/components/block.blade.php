{{--
    A block of a public page (DESIGN-SYSTEM.md, "Blocks"): a Filament section,
    collapsible and compact by default. heading, description, icon, id and
    classes pass through; so does the afterHeader slot.

    - table: a Filament table fills the block edge to edge, with denser rows.
    - Opening a folded block fires a window resize, so a Leaflet map or chart
      inside measures itself again (it cannot while hidden).
--}}
@props ([
    'collapsible' => true,
    'compact' => true,
    'table' => false,
])

<x-filament::section
    :collapsible="$collapsible"
    :compact="$compact"
    x-init="
        $watch(
            'isCollapsed',
            (collapsed) => collapsed || setTimeout(() => window.dispatchEvent(new Event('resize')), 50),
        )
    "
    {{ $attributes->class([
        'scroll-mt-24',
        '[&_.fi-section-content]:p-0 [&_.fi-ta-ctn]:rounded-none [&_.fi-ta-ctn]:shadow-none [&_.fi-ta-ctn]:ring-0 [&_td_.fi-ta-text]:py-2 [&_td:has(.fi-ta-actions)]:py-2' => $table,
    ]) }}
>
    @isset ($afterHeader)
        <x-slot name="afterHeader">
            {{ $afterHeader }}
        </x-slot>
    @endisset

    {{ $slot }}
</x-filament::section>
