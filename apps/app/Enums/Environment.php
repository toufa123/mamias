<?php

    namespace App\Enums;

    use Filament\Support\Contracts\HasColor;
    use Filament\Support\Contracts\HasIcon;
    use Filament\Support\Contracts\HasLabel;

    enum Environment: string implements HasLabel, HasColor, HasIcon
    {
        case marine = 'marine';
        case freshwater = 'freshwater';
        case brackish = 'brackish';
        case terrestrial = 'terrestrial';

        public function getLabel(): ?string
        {
            return match ($this) {
                self::marine => 'Marine',
                self::freshwater => 'Freshwater',
                self::brackish => 'Brackish',
                self::terrestrial => 'Terrestrial',
            };
        }

        public function getColor(): string|array|null
        {
            return match ($this) {
                self::marine => '#4166F5',
                self::freshwater => '#45ADA8',
                self::brackish => '#8E7F73',
                self::terrestrial => '#A0A0A0',
            };
        }

        public function getIcon(): ?string
        {
            return match ($this) {
                self::marine => 'tabler-waves',
                self::freshwater => 'tabler-droplet',
                self::brackish => 'tabler-ripple',
                self::terrestrial => 'tabler-mountain',
            };
        }

        public function label(): string
        {
            return $this->getLabel();
        }

        public static function fromLabelOrValue(null|string|self $state): ?self
        {
            if ($state instanceof self) {
                return $state;
            }

            $normalized = strtolower(trim((string) $state));

            if ($normalized === '') {
                return null;
            }

            return self::tryFrom($normalized)
                ?? collect(self::cases())->first(
                    fn (self $case) => strtolower((string) $case->getLabel()) === $normalized
                );
        }

        public static function fromWormsData(array $item): array
        {
            $environments = [];

            if (! empty($item['isMarine'])) {
                $environments[] = self::marine->value;
            }

            if (! empty($item['isBrackish'])) {
                $environments[] = self::brackish->value;
            }

            if (! empty($item['isFreshwater'])) {
                $environments[] = self::freshwater->value;
            }

            if (! empty($item['isTerrestrial'])) {
                $environments[] = self::terrestrial->value;
            }

            return $environments;
        }
    }
