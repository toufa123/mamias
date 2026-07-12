<?php

namespace App\Services;

use App\Enums\Catalogue_Status;
use App\Enums\Environment;
use App\Enums\Worms_Status;

/**
 * Helper for comparing and building form-state representations of taxon data.
 *
 * Used by TaxonService to compute differences between current form values
 * and newly fetched WoRMS data, supporting partial updates with notifications.
 */
class TaxonStateHelper
{
    public function __construct(
        private readonly TaxonNormalizer $taxonNormalizer
    ) {}

    /**
     * @param  array<string, mixed>  $currentState
     * @param  array<string, mixed>  $incomingState
     * @return array<string>
     */
    public function getChangedFieldLabels(array $currentState, array $incomingState): array
    {
        $changedFields = [];
        foreach ($incomingState as $field => $newValue) {
            $oldValue = $currentState[$field] ?? null;

            if ($newValue !== $oldValue) {
                $changedFields[] = $this->getFetchedDataFieldLabel($field);
            }
        }

        return $changedFields;
    }

    /**
     * Get a human-readable label for a taxon field key.
     */
    public function getFetchedDataFieldLabel(string $field): string
    {
        $labels = [
            'scientificname' => 'Scientific Name',
            'authority' => 'Authority',
            'aphia_id' => 'Aphia ID',
            'url' => 'WoRMS URL',
            'lsid' => 'LSID',
            'unacceptreason' => 'Unaccept Reason',
            'kingdom' => 'Kingdom',
            'phylum' => 'Phylum',
            'class' => 'Class',
            'order' => 'Order',
            'family' => 'Family',
            'genus' => 'Genus',
            'rank' => 'Taxonomic Rank',
            'worms_status' => 'WoRMS Status',
            'catalogue_status' => 'Catalogue Status',
            'environments' => 'Environment',
            'is_extinct' => 'Extinct',
            'synonyms_data' => 'Synonyms',
            'Easin_id' => 'EASIN ID',
            'notes' => 'Notes',
        ];

        return $labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Build normalised "current" and "incoming" state arrays for comparison.
     *
     * @param  array<string, mixed>  $currentValues
     * @param  array<string, mixed>  $wormsRecord
     * @param  array<int, mixed>  $formattedSynonyms
     * @return array{array<string, mixed>, array<string, mixed>}
     */
    public function buildFetchedDataStates(array $currentValues, array $wormsRecord, array $formattedSynonyms, ?string $notes, ?string $proposedAcceptedName = null): array
    {
        $wormsStatus = $currentValues['worms_status'] ?? '';
        if ($wormsStatus instanceof Worms_Status) {
            $wormsStatusValue = $wormsStatus->value;
        } else {
            $wormsStatusValue = Worms_Status::tryFrom((string) $wormsStatus)?->value;
        }

        $currentState = [
            'scientificname' => $this->taxonNormalizer->normalizeNullableString($currentValues['scientificname'] ?? null),
            'authority' => $this->taxonNormalizer->normalizeNullableString($currentValues['authority'] ?? null),
            'aphia_id' => $this->taxonNormalizer->normalizeNullableInt($currentValues['aphia_id'] ?? null),
            'url' => $this->taxonNormalizer->normalizeNullableString($currentValues['url'] ?? null),
            'lsid' => $this->taxonNormalizer->normalizeNullableString($currentValues['lsid'] ?? null),
            'unacceptreason' => $this->taxonNormalizer->normalizeNullableString($currentValues['unacceptreason'] ?? null),
            'kingdom' => $this->taxonNormalizer->normalizeNullableString($currentValues['kingdom'] ?? null),
            'phylum' => $this->taxonNormalizer->normalizeNullableString($currentValues['phylum'] ?? null),
            'class' => $this->taxonNormalizer->normalizeNullableString($currentValues['class'] ?? null),
            'order' => $this->taxonNormalizer->normalizeNullableString($currentValues['order'] ?? null),
            'family' => $this->taxonNormalizer->normalizeNullableString($currentValues['family'] ?? null),
            'genus' => $this->taxonNormalizer->normalizeNullableString($currentValues['genus'] ?? null),
            'rank' => $this->taxonNormalizer->normalizeNullableString($currentValues['rank'] ?? null),
            'worms_status' => $wormsStatusValue,
            'catalogue_status' => $this->taxonNormalizer->normalizeNullableString($currentValues['catalogue_status'] ?? null),
            'environments' => $this->taxonNormalizer->normalizeEnvironments($currentValues['environments'] ?? null),
            'is_extinct' => (bool) ($currentValues['is_extinct'] ?? false),
            'synonyms_data' => $this->taxonNormalizer->normalizeSynonyms($currentValues['synonyms_data'] ?? null),
            'Easin_id' => $this->taxonNormalizer->normalizeNullableString($currentValues['Easin_id'] ?? null),
            'notes' => $this->taxonNormalizer->normalizeNullableString($currentValues['notes'] ?? null),
            'proposed_accepted_name' => $this->taxonNormalizer->normalizeNullableString($currentValues['proposed_accepted_name'] ?? null),
        ];

        $incomingWormsStatus = $wormsRecord['status'] ?? '';
        if ($incomingWormsStatus instanceof Worms_Status) {
            $incomingWormsStatusValue = $incomingWormsStatus->value;
        } else {
            $incomingWormsStatusValue = Worms_Status::tryFrom((string) $incomingWormsStatus)?->value;
        }

        $incomingState = [
            'scientificname' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['scientificname'] ?? null),
            'authority' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['authority'] ?? null),
            'aphia_id' => $this->taxonNormalizer->normalizeNullableInt($wormsRecord['AphiaID'] ?? null),
            'url' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['url'] ?? null),
            'lsid' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['lsid'] ?? null),
            'unacceptreason' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['unacceptreason'] ?? null),
            'kingdom' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['kingdom'] ?? null),
            'phylum' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['phylum'] ?? null),
            'class' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['class'] ?? null),
            'order' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['order'] ?? null),
            'family' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['family'] ?? null),
            'genus' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['genus'] ?? null),
            'rank' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['rank'] ?? null),
            'worms_status' => $incomingWormsStatusValue,
            'catalogue_status' => $this->taxonNormalizer->normalizeNullableString(Catalogue_Status::fromWormsData($wormsRecord['status'] ?? null)->value),
            'environments' => $this->taxonNormalizer->normalizeEnvironments(Environment::fromWormsData($wormsRecord)),
            'is_extinct' => ($wormsRecord['isExtinct'] ?? 0) == 1,
            'synonyms_data' => $this->taxonNormalizer->normalizeSynonyms($formattedSynonyms),
            'Easin_id' => $this->taxonNormalizer->normalizeNullableString($wormsRecord['Easin_id'] ?? null),
            'notes' => $this->taxonNormalizer->normalizeNullableString($notes),
            'proposed_accepted_name' => $this->taxonNormalizer->normalizeNullableString($proposedAcceptedName),
        ];

        return [$currentState, $incomingState];
    }

    /**
     * Format a WoRMS record and its synonyms into a form-friendly state array.
     */
    public function formatWormsDataForForm(array $record, array $formattedSynonyms): array
    {
        $wormsStatus = $record['status'] ?? '';
        if ($wormsStatus instanceof Worms_Status) {
            $wormsStatusValue = $wormsStatus->value;
        } else {
            $wormsStatusValue = Worms_Status::tryFrom((string) $wormsStatus)?->value;
        }

        return [
            'scientificname' => $this->taxonNormalizer->normalizeNullableString($record['scientificname'] ?? null),
            'authority' => $this->taxonNormalizer->normalizeNullableString($record['authority'] ?? null),
            'aphia_id' => $this->taxonNormalizer->normalizeNullableInt($record['AphiaID'] ?? null),
            'url' => $this->taxonNormalizer->normalizeNullableString($record['url'] ?? null),
            'lsid' => $this->taxonNormalizer->normalizeNullableString($record['lsid'] ?? null),
            'unacceptreason' => $this->taxonNormalizer->normalizeNullableString($record['unacceptreason'] ?? null),
            'kingdom' => $this->taxonNormalizer->normalizeNullableString($record['kingdom'] ?? null),
            'phylum' => $this->taxonNormalizer->normalizeNullableString($record['phylum'] ?? null),
            'class' => $this->taxonNormalizer->normalizeNullableString($record['class'] ?? null),
            'order' => $this->taxonNormalizer->normalizeNullableString($record['order'] ?? null),
            'family' => $this->taxonNormalizer->normalizeNullableString($record['family'] ?? null),
            'genus' => $this->taxonNormalizer->normalizeNullableString($record['genus'] ?? null),
            'rank' => $this->taxonNormalizer->normalizeNullableString($record['rank'] ?? null),
            'worms_status' => $wormsStatusValue,
            'catalogue_status' => Catalogue_Status::fromWormsData($record['status'] ?? null)->value,
            'environments' => $this->taxonNormalizer->normalizeEnvironments(Environment::fromWormsData($record)),
            'is_extinct' => ($record['isExtinct'] ?? 0) == 1,
            'synonyms_data' => $formattedSynonyms,
            'Easin_id' => $this->taxonNormalizer->normalizeNullableString($record['Easin_id'] ?? null),
            'proposed_accepted_name' => null,
        ];
    }
}
