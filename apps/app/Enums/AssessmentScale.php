<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Geographic scale for risk or impact assessments.
 *
 * Used to distinguish assessments conducted at global, regional (e.g., Mediterranean),
 * or national level.
 */
enum AssessmentScale: string implements HasLabel
{
    /** Assessment at global scale. */
    case Global = 'Global';

    /** Assessment at regional scale (e.g., Mediterranean Sea basin). */
    case Regional = 'Regional';

    /** Assessment at national scale (individual country). */
    case National = 'National';

    /**
     * Human-readable label for the assessment scale.
     */
    public function getLabel(): ?string
    {
        return $this->value;
    }
}
