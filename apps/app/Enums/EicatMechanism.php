<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * EICAT impact mechanisms — the specific ecological mechanism through which
 * an alien species causes environmental impact.
 *
 * Based on the IUCN EICAT framework categories of impact mechanism.
 */
enum EicatMechanism: string implements HasLabel
{
    /** Competition with native species for resources. */
    case Competition = 'Competition';

    /** Predation on native species. */
    case Predation = 'Predation';

    /** Herbivory or grazing on native plants/algae. */
    case Herbivory = 'Herbivory';

    /** Transmission of diseases to native species. */
    case DiseaseTransmission = 'Disease transmission';

    /** Parasitism of native species. */
    case Parasitism = 'Parasitism';

    /** Poisoning or toxicity affecting native biota. */
    case PoisoningToxicity = 'Poisoning/toxicity';

    /** Bio-fouling (e.g., on hulls, structures, or other organisms). */
    case BioFouling = 'Bio-fouling';

    /** Hybridisation with native species leading to genetic introgression. */
    case Hybridisation = 'Hybridisation';

    /** Physical trampling or disturbance. */
    case Trampling = 'Trampling';

    /** Chemical impact on ecosystem processes (e.g., nutrient cycling). */
    case ChemicalImpact = 'Chemical impact on ecosystem';

    /** Structural impact on physical habitat or ecosystem structure. */
    case StructuralImpact = 'Structural impact on ecosystem';

    /** Other mechanism not covered by the above categories. */
    case Other = 'Other';

    /**
     * Human-readable label for the impact mechanism.
     */
    public function getLabel(): ?string
    {
        return $this->value;
    }
}
