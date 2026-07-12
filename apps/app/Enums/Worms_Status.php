<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * WoRMS (World Register of Marine Species) taxonomic status values.
 *
 * Mirrors the full set of taxonomic status codes used by WoRMS to describe
 * the nomenclatural and taxonomic standing of a species name.
 */
enum Worms_Status: string implements HasColor, HasIcon, HasLabel
{
    /** Currently accepted name. */
    case accepted = 'accepted';

    /** Not accepted — superseded or invalid. */
    case unaccepted = 'unaccepted';

    /** Name of doubtful application (nomen dubium). */
    case nomen_dubium = 'nomen dubium';

    /** Name published without a description (nomen nudum). */
    case nomen_nudum = 'nomen nudum';

    /** Name requiring further investigation (taxon inquirendum). */
    case taxon_inquirendum = 'taxon inquirendum';

    /** Name published but not yet validly published. */
    case interim_unpublished = 'interim unpublished';

    /** Taxon record deleted from WoRMS. */
    case deleted = 'deleted';

    /** Taxonomic status is uncertain. */
    case uncertain = 'uncertain';

    /** Alternative representation of the same taxon. */
    case alternative_representation = 'alternative representation';

    /** Temporary name placeholder. */
    case temporary_name = 'temporary name';

    /** Superseded combination (moved to another genus). */
    case superseded_combination = 'superseded combination';

    /** Junior homonym (same name used for different taxon). */
    case junior_homonym = 'junior homonym';

    /** Name misapplied to this taxon. */
    case misapplication = 'misapplication';

    /** Taxonomic discrepancy between sources. */
    case taxonomic_discrepancy = 'taxonomic discrepancy';

    /** Status not yet assessed. */
    case unassessed = 'unassessed';

    /** Misspelling — incorrect subsequent spelling. */
    case misspelling_incorrect_subsequent_spelling = 'misspelling - incorrect subsequent spelling';

    /** Misspelling — incorrect original spelling. */
    case misspelling_incorrect_original_spelling = 'misspelling - incorrect original spelling';

    /** Junior subjective synonym. */
    case junior_subjective_synonym = 'junior subjective synonym';

    /** Junior objective synonym. */
    case junior_objective_synonym = 'junior objective synonym';

    /** Name forgotten or unused (nomen oblitum). */
    case nomen_oblitum = 'nomen oblitum';

    /** General misspelling. */
    case misspelling = 'misspelling';

    /** Unjustified emendation of the original name. */
    case unjustified_emendation = 'unjustified emendation';

    /** Incorrect grammatical agreement. */
    case incorrect_grammatical_agreement = 'incorrect grammatical agreement';

    /** Name not available under nomenclatural rules. */
    case unavailable_name = 'unavailable name';

    /** Superseded rank classification. */
    case superseded_rank = 'superseded rank';

    /** Name rejected under nomenclatural rules (nomen rejiciendum). */
    case nomen_rejiciendum = 'nomen rejiciendum';

    /** Unreplaced junior homonym. */
    case unreplaced_junior_homonym = 'unreplaced junior homonym';

    /** Uncertain taxonomic placement (incertae sedis). */
    case incertae_sedis = 'incertae sedis';

    /**
     * Human-readable label for the WoRMS taxonomic status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::accepted => 'Accepted',
            self::unaccepted => 'Unaccepted',
            self::nomen_dubium => 'Nomen dubium',
            self::nomen_nudum => 'Nomen nudum',
            self::taxon_inquirendum => 'Taxon inquirendum',
            self::interim_unpublished => 'Interim unpublished',
            self::deleted => 'Deleted',
            self::uncertain => 'Uncertain',
            self::alternative_representation => 'Alternative representation',
            self::temporary_name => 'Temporary name',
            self::superseded_combination => 'Superseded combination',
            self::junior_homonym => 'Junior homonym',
            self::misapplication => 'Misapplication',
            self::taxonomic_discrepancy => 'Taxonomic discrepancy',
            self::unassessed => 'Unassessed',
            self::misspelling_incorrect_subsequent_spelling => 'Misspelling - incorrect subsequent spelling',
            self::misspelling_incorrect_original_spelling => 'Misspelling - incorrect original spelling',
            self::junior_subjective_synonym => 'Junior subjective synonym',
            self::junior_objective_synonym => 'Junior objective synonym',
            self::nomen_oblitum => 'Nomen oblitum',
            self::misspelling => 'Misspelling',
            self::unjustified_emendation => 'Unjustified emendation',
            self::incorrect_grammatical_agreement => 'Incorrect grammatical agreement',
            self::unavailable_name => 'Unavailable name',
            self::superseded_rank => 'Superseded rank',
            self::nomen_rejiciendum => 'Nomen rejiciendum',
            self::unreplaced_junior_homonym => 'Unreplaced junior homonym',
            self::incertae_sedis => 'Incertae sedis',
        };
    }

    /**
     * Filament color for UI display.
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::accepted => 'success',
            self::unaccepted, self::superseded_combination, self::junior_homonym, self::misapplication, self::misspelling_incorrect_subsequent_spelling, self::misspelling_incorrect_original_spelling, self::junior_subjective_synonym, self::junior_objective_synonym, self::nomen_oblitum, self::misspelling, self::unjustified_emendation, self::incorrect_grammatical_agreement, self::unavailable_name, self::superseded_rank, self::nomen_rejiciendum, self::unreplaced_junior_homonym => 'danger',
            self::nomen_dubium, self::nomen_nudum, self::taxon_inquirendum, self::taxonomic_discrepancy, self::unassessed, self::incertae_sedis => 'warning',
            default => 'gray',
        };
    }

    /**
     * Filament icon for UI display.
     */
    public function getIcon(): ?string
    {
        return match ($this) {
            self::accepted => 'tabler-circle-check',
            self::unaccepted, self::superseded_combination, self::junior_homonym, self::misapplication, self::misspelling_incorrect_subsequent_spelling, self::misspelling_incorrect_original_spelling, self::junior_subjective_synonym, self::junior_objective_synonym, self::nomen_oblitum, self::misspelling, self::unjustified_emendation, self::incorrect_grammatical_agreement, self::unavailable_name, self::superseded_rank, self::nomen_rejiciendum, self::unreplaced_junior_homonym => 'tabler-circle-x',
            self::nomen_dubium, self::nomen_nudum, self::taxon_inquirendum, self::taxonomic_discrepancy, self::unassessed, self::incertae_sedis => 'tabler-alert-circle',
            default => 'tabler-help-circle',
        };
    }
}
