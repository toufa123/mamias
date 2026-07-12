<?php

namespace App\Filament\Forms\Components;

use YousefAman\ModalRepeater\Column;
use YousefAman\ModalRepeater\ModalRepeater;

/**
 * Modal repeater that overrides item display value resolution to support
 * both record-based and raw state-based column value rendering.
 */
class MapModalRepeater extends ModalRepeater
{
    /**
     * Resolves display values for a given repeater item by reading from
     * the raw state or the related record, then applying column formatting.
     *
     * @param  string  $itemKey  The key of the repeater item.
     * @return array<string, mixed>
     */
    public function getItemDisplayValues(string $itemKey): array
    {
        $items = $this->getRawState();
        $itemState = $items[$itemKey] ?? [];
        $record = $this->resolveItemRecord($itemKey, $itemState);
        $values = [];

        foreach ($this->getDisplayColumns() as $column) {
            $name = $column->getName();
            $rawValue = $record
                ? data_get($record, $name)
                : data_get($itemState, $name);

            if ($column instanceof Column) {
                $values[$name] = $column->resolveValue($rawValue);
            } else {
                $values[$name] = $rawValue;
            }
        }

        return $values;
    }
}
