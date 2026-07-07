@php
    use Filament\Actions\Action;
    use Filament\Support\Enums\Alignment;
    use Illuminate\View\ComponentAttributeBag;

    $fieldWrapperView = $getFieldWrapperView();

    $items = $getItems();
    $rawState = $getRawState() ?? [];

    $addAction = $getAction($getAddActionName());
    $addActionAlignment = $getAddActionAlignment();
    $editAction = $getAction('edit');
    $cloneAction = $getAction($getCloneActionName());
    $deleteAction = $getAction($getDeleteActionName());
    $moveDownAction = $getAction($getMoveDownActionName());
    $moveUpAction = $getAction($getMoveUpActionName());
    $reorderAction = $getAction($getReorderActionName());
    $extraItemActions = $getExtraItemActions();

    $isAddable = $isAddable();
    $isCloneable = $isCloneable();
    $isDeletable = $isDeletable();
    $isReorderableWithButtons = $isReorderableWithButtons();
    $isReorderableWithDragAndDrop = $isReorderableWithDragAndDrop();

    $displayColumns = $getDisplayColumns();

    $key = $getKey();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        {{ $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class(['fi-fo-modal-repeater']) }}
    >
        @if (count($items))
            <table class="w-full border border-gray-200 divide-y divide-gray-200 overflow-hidden rounded-lg">
                <thead>
                    <tr>
                        @if ((count($items) > 1) && ($isReorderableWithButtons || $isReorderableWithDragAndDrop))
                            <th class="px-3 py-3.5 bg-gray-50"></th>
                        @endif

                        @foreach ($displayColumns as $column)
                            <th
                                class="px-3 py-3.5 text-sm font-semibold text-gray-950 bg-gray-50 text-left"
                                @style([
                                    ('width: ' . $column->getWidth()) => $column->getWidth(),
                                ])
                            >
                                {{ $column->getLabel() }}
                            </th>
                        @endforeach

                        @if ($editAction->isVisible() || count($extraItemActions) || $isCloneable || $isDeletable)
                            <th class="px-3 py-3.5 bg-gray-50"></th>
                        @endif
                    </tr>
                </thead>

                <tbody
                    @if ($isReorderableWithDragAndDrop)
                        x-sortable
                        {{ (new ComponentAttributeBag)
                                ->merge([
                                    'data-sortable-animation-duration' => $getReorderAnimationDuration(),
                                    'x-on:end.stop' => '$wire.mountAction(\'reorder\', { items: $event.target.sortable.toArray() }, { schemaComponent: \'' . $key . '\' })',
                                ], escape: false) }}
                    @endif
                >
                    @foreach ($items as $itemKey => $item)
                        @php
                            $visibleExtraItemActions = array_filter(
                                $extraItemActions,
                                fn (Action $action): bool => $action(['item' => $itemKey])->isVisible(),
                            );
                            $itemCloneAction = $cloneAction(['item' => $itemKey]);
                            $cloneActionIsVisible = $isCloneable && $itemCloneAction->isVisible();
                            $itemDeleteAction = $deleteAction(['item' => $itemKey]);
                            $deleteActionIsVisible = $isDeletable && $itemDeleteAction->isVisible();
                            $itemMoveDownAction = $moveDownAction(['item' => $itemKey])->disabled($loop->last);
                            $moveDownActionIsVisible = $isReorderableWithButtons && $itemMoveDownAction->isVisible();
                            $itemMoveUpAction = $moveUpAction(['item' => $itemKey])->disabled($loop->first);
                            $moveUpActionIsVisible = $isReorderableWithButtons && $itemMoveUpAction->isVisible();
                            $reorderActionIsVisible = $isReorderableWithDragAndDrop && $reorderAction->isVisible();
                            $displayValues = $getItemDisplayValues($itemKey);
                        @endphp

                        <tr
                            wire:key="{{ $item->getLivewireKey() }}.item"
                            class="hover:bg-gray-50"
                            @if ($isReorderableWithDragAndDrop)
                                x-sortable-item="{{ $itemKey }}"
                            @endif
                        >
                            @if ((count($items) > 1) && ($isReorderableWithButtons || $isReorderableWithDragAndDrop))
                                <td class="px-3 py-4">
                                    @if ($reorderActionIsVisible || $moveUpActionIsVisible || $moveDownActionIsVisible)
                                        <div class="fi-fo-modal-repeater-actions">
                                            @if ($reorderActionIsVisible)
                                                <div x-on:click.stop>
                                                    {{ $reorderAction->extraAttributes(['x-sortable-handle' => true], merge: true) }}
                                                </div>
                                            @endif

                                            @if ($moveUpActionIsVisible || $moveDownActionIsVisible)
                                                <div x-on:click.stop>
                                                    {{ $itemMoveUpAction }}
                                                </div>

                                                <div x-on:click.stop>
                                                    {{ $itemMoveDownAction }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endif

                            @foreach ($displayColumns as $column)
                                <td class="px-3 py-4 text-sm text-gray-950">
                                @php
                                    $isMapColumn = $column instanceof \EduardoRibeiroDev\FilamentLeaflet\Tables\MapColumn;
                                @endphp
                                @if ($isMapColumn)
                                    @php
                                        $loc = $rawState[$itemKey]['location'] ?? null;
                                        $lat = null;
                                        $lng = null;
                                        if (is_array($loc)) {
                                            if (isset($loc['lat'], $loc['lng'])) {
                                                $lat = $loc['lat'];
                                                $lng = $loc['lng'];
                                            } elseif (isset($loc[0]['lat'], $loc[0]['lng'])) {
                                                $lat = $loc[0]['lat'];
                                                $lng = $loc[0]['lng'];
                                            }
                                        }
                                    @endphp
                                    @if ($lat && $lng)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-danger-500 inline-block align-middle" title="{{ $lat }}, {{ $lng }}">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <span class="text-xs text-gray-500 align-middle ml-1">{{ number_format($lat, 4) }}, {{ number_format($lng, 4) }}</span>
                                    @else
                                        -
                                    @endif
                                    @else
                                        {{ $displayValues[$column->getName()] ?? '-' }}
                                    @endif
                                </td>
                            @endforeach

                            @if ($editAction->isVisible() || count($visibleExtraItemActions) || $cloneActionIsVisible || $deleteActionIsVisible)
                                <td class="px-3 py-4 text-sm text-gray-950">
                                    <div class="fi-fo-modal-repeater-actions">
                                        @if ($editAction->isVisible())
                                            <div x-on:click.stop>
                                                {{ $editAction(['item' => $itemKey]) }}
                                            </div>
                                        @endif

                                        @foreach ($visibleExtraItemActions as $extraItemAction)
                                            <div x-on:click.stop>
                                                {{ $extraItemAction(['item' => $itemKey]) }}
                                            </div>
                                        @endforeach

                                        @if ($cloneActionIsVisible)
                                            <div x-on:click.stop>
                                                {{ $itemCloneAction }}
                                            </div>
                                        @endif

                                        @if ($deleteActionIsVisible)
                                            <div x-on:click.stop>
                                                {{ $itemDeleteAction }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="fi-fo-modal-repeater-empty">
                <p class="fi-fo-modal-repeater-empty-text">
                    {{ $getEmptyLabel() }}
                </p>
            </div>
        @endif

        @if ($isAddable && $addAction->isVisible())
            <div
                @class([
                    'fi-fo-modal-repeater-add',
                    ($addActionAlignment instanceof Alignment) ? ('fi-align-' . $addActionAlignment->value) : $addActionAlignment,
                ])
            >
                {{ $addAction }}
            </div>
        @endif
    </div>
</x-dynamic-component>
