<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Worms_Status: string implements HasColor, HasIcon, HasLabel
{
    case accepted = 'accepted';
    case unaccepted = 'unaccepted';
    case nomen_dubium = 'nomen dubium';
    case nomen_nudum = 'nomen nudum';
    case taxon_inquirendum = 'taxon inquirendum';
    case interim_unpublished = 'interim unpublished';
    case deleted = 'deleted';
    case uncertain = 'uncertain';
    case alternative_representation = 'alternative representation';
    case temporary_name = 'temporary name';
    case superseded_combination = 'superseded combination';
    case junior_homonym = 'junior homonym';
    case misapplication = 'misapplication';
    case taxonomic_discrepancy = 'taxonomic discrepancy';
    case unassessed = 'unassessed';
    case misspelling_incorrect_subsequent_spelling = 'misspelling - incorrect subsequent spelling';
    case misspelling_incorrect_original_spelling = 'misspelling - incorrect original spelling';
    case junior_subjective_synonym = 'junior subjective synonym';
    case junior_objective_synonym = 'junior objective synonym';
    case nomen_oblitum = 'nomen oblitum';
    case misspelling = 'misspelling';
    case unjustified_emendation = 'unjustified emendation';
    case incorrect_grammatical_agreement = 'incorrect grammatical agreement';
    case unavailable_name = 'unavailable name';
    case superseded_rank = 'superseded rank';
    case nomen_rejiciendum = 'nomen rejiciendum';
    case unreplaced_junior_homonym = 'unreplaced junior homonym';
    case incertae_sedis = 'incertae sedis';

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

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::accepted => 'success',
            self::unaccepted, self::superseded_combination, self::junior_homonym, self::misapplication, self::misspelling_incorrect_subsequent_spelling, self::misspelling_incorrect_original_spelling, self::junior_subjective_synonym, self::junior_objective_synonym, self::nomen_oblitum, self::misspelling, self::unjustified_emendation, self::incorrect_grammatical_agreement, self::unavailable_name, self::superseded_rank, self::nomen_rejiciendum, self::unreplaced_junior_homonym => 'danger',
            self::nomen_dubium, self::nomen_nudum, self::taxon_inquirendum, self::taxonomic_discrepancy, self::unassessed, self::incertae_sedis => 'warning',
            default => 'gray',
        };
    }

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
