<?php
    
    namespace App\Enums;
    
    use Filament\Support\Contracts\HasColor;
    use Filament\Support\Contracts\HasDescription;
    use Filament\Support\Contracts\HasIcon;
    use Filament\Support\Contracts\HasLabel;
    
    enum NisStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
    {
        case NIS = 'NIS';
        case Cryptogenic = 'Cryptogenic';
        case Questionable = 'Questionable';
        
        public function getLabel(): ?string
        {
            return $this->value;
        }
        
        public function getDescription(): ?string
        {
            return match ($this) {
                self::NIS => 'Species introduced outside its native range',
                self::Cryptogenic => 'Species with unknown native range or pathway of introduction',
                self::Questionable => 'Species with unresolved taxonomic status or not verified by experts',
            };
        }
        
        public function getColor(): string|array|null
        {
            return match ($this) {
                self::NIS => 'success',
                self::Cryptogenic => 'warning',
                self::Questionable => 'danger',
            };
        }
        
        public function getIcon(): ?string
        {
            return match ($this) {
                self::NIS => 'tabler-world',
                self::Cryptogenic => 'tabler-help',
                self::Questionable => 'tabler-alert-circle',
            };
        }
    }
