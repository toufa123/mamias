<?php

    namespace App\Enums;

    use Filament\Support\Contracts\HasColor;
    use Filament\Support\Contracts\HasIcon;
    use Filament\Support\Contracts\HasLabel;

    enum Subregion: string implements HasLabel, HasColor, HasIcon
    {
        case WMED = 'WMED';
        case CMED = 'CMED';
        case ADRIA = 'ADRIA';
        case EMED = 'EMED';

        public function getLabel(): ?string
        {
            return match ($this) {
                self::WMED => 'Western Mediterranean',
                self::CMED => 'Central Mediterranean',
                self::ADRIA => 'Adriatic Sea',
                self::EMED => 'Eastern Mediterranean',
            };
        }

        public function getColor(): string|array|null
        {
            return 'primary';
        }

        public function getIcon(): ?string
        {
            return 'tabler-map-pin';
        }

        public function label(): string
        {
            return $this->getLabel();
        }
    }
