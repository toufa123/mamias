<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Publication types for literature references in the catalogue.
 *
 * Classifies each reference by its format (journal article, report, thesis, etc.)
 * for filtering and display in the Filament panel.
 */
enum LiteratureType: string implements HasIcon, HasLabel
{
    /** Peer-reviewed journal article. */
    case ARTICLE = 'article';

    /** Technical or project report. */
    case TECHNICAL_REPORT = 'technical_report';

    /** PhD or MSc thesis. */
    case THESIS = 'thesis';

    /** Book or book chapter. */
    case BOOK = 'book';

    /** Conference proceedings paper. */
    case CONFERENCE_PROCEEDINGS = 'conference_proceedings';

    /**
     * Human-readable label for the literature type.
     */
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

    /**
     * Filament icon for UI display.
     */
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
