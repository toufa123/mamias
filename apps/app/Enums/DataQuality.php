<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DataQuality: string implements HasColor, HasIcon, HasLabel
{
    case NA = 'N/A';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function getLabel(): ?string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NA => 'gray',
            self::High => 'success',
            self::Medium => 'warning',
            self::Low => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NA => 'tabler-number-0-small',
            self::High => 'tabler-shield-check',
            self::Medium => 'tabler-shield-exclamation',
            self::Low => 'tabler-shield-off',
        };
    }
}
