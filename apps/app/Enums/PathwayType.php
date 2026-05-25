<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PathwayType: string implements HasLabel
{
    case Primary = 'primary';
    case Secondary = 'secondary';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Primary => 'Primary',
            self::Secondary => 'Secondary',
        };
    }
}
