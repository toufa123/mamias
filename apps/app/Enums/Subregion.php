<?php
    
    declare(strict_types=1);
    
    namespace App\Enums;
    
    use Filament\Support\Contracts\HasLabel;
    
    /**
     * EcAp Mediterranean sub-regions.
     *
     * @see https://www.rac-spa.org/fr/ecap
     */
    enum Subregion: string implements HasLabel
    {
        /** Western Mediterranean */
        case WMED = 'wmed';
        
        /** Central Mediterranean */
        case CMED = 'cmed';
        
        /** Adriatic Sea */
        case ADRIA = 'adria';
        
        /** Eastern Mediterranean */
        case EMED = 'emed';
        
        public function getLabel(): ?string
        {
            return match ($this) {
                self::WMED => 'Western Mediterranean (WMED)',
                self::CMED => 'Central Mediterranean (CMED)',
                self::ADRIA => 'Adriatic Sea (ADRIA)',
                self::EMED => 'Eastern Mediterranean (EMED)',
            };
        }
    }
