<?php

namespace App\Filament\Forms\Components;

use YousefAman\ModalRepeater\Column;
use YousefAman\ModalRepeater\ModalRepeater;

class MapModalRepeater extends ModalRepeater
{
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
