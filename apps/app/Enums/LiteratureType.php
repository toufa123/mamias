<?php
    
    namespace App\Enums;
    
    use Filament\Support\Contracts\HasIcon;
    use Filament\Support\Contracts\HasLabel;
    
    enum LiteratureType: string implements HasLabel, HasIcon
    {
        case ARTICLE = 'article';
        case TECHNICAL_REPORT = 'technical_report';
        case THESIS = 'thesis';
        case BOOK = 'book';
        case CONFERENCE_PROCEEDINGS = 'conference_proceedings';
        
        public function getLabel(): ?string
        {
            return match ($this) {
                self::ARTICLE => 'Article',
                self::TECHNICAL_REPORT => 'Technical Report',
                self::THESIS => 'Thesis',
                self::BOOK => 'Book',
                self::CONFERENCE_PROCEEDINGS => 'Conference Proceedings',
            };
        }
        
        public function getIcon(): ?string
        {
            return match ($this) {
                self::ARTICLE => 'tabler-news',
                self::TECHNICAL_REPORT => 'tabler-file-analytics',
                self::THESIS => 'tabler-school',
                self::BOOK => 'tabler-book',
                self::CONFERENCE_PROCEEDINGS => 'tabler-users',
            };
        }
    }
