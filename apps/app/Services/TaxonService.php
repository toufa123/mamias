<?php

namespace App\Services;

use App\Enums\Catalogue_Status;
use App\Models\Taxon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class TaxonService
{
    public function __construct(
        private readonly WormsService $wormsService,
        private readonly TaxonNormalizer $taxonNormalizer,
        private readonly TaxonStateHelper $stateHelper,
        private readonly EasinService $easinService,
    ) {}

    /**
     * @return array{updated:int, missing_aphia_id:int, not_found:int}
     */
    final public function refreshFromWorms(Collection $records, ?callable $onProgress = null): array
    {
        $updated = 0;
        $missingAphiaId = 0;
        $notFound = 0;

        foreach ($records as $taxon) {
            if (! $taxon instanceof Taxon) {
                continue;
            }

            // Normalization is also handled by model booted saving event,
            // but we do it here explicitly as it was in the original code.
            $this->taxonNormalizer->normalize($taxon);

            $wormsData = $this->wormsService->getRecordByName($taxon->scientificname);

            if (! is_array($wormsData) || $wormsData === []) {
                $notFound++;
                $taxon->catalogue_status = Catalogue_Status::no_data_from_worms;
                $taxon->save();

                if ($onProgress) {
                    $onProgress();
                }

                continue;
            }

            $this->wormsService->populateTaxonFromWorms($taxon, $wormsData);

            if (! $taxon->Easin_id) {
                $taxon->Easin_id = $this->easinService->fetchEasinId($taxon->scientificname);
            }

            if (! $this->hasMeaningfulChanges($taxon)) {
                $taxon->save();

                if ($onProgress) {
                    $onProgress();
                }

                continue;
            }

            $taxon->save();
            $updated++;

            if ($onProgress) {
                $onProgress();
            }
        }

        return [
            'updated' => $updated,
            'missing_aphia_id' => $missingAphiaId,
            'not_found' => $notFound,
        ];
    }

    final public function hasMeaningfulChanges(Taxon $taxon): bool
    {
        $dirtyAttributes = array_diff(array_keys($taxon->getDirty()), ['fetched_at', 'updated_at']);

        foreach ($dirtyAttributes as $attribute) {
            if ($attribute === 'unacceptreason') {
                $currentValue = $taxon->unacceptreason;
                $originalValue = $taxon->getOriginal('unacceptreason');

                if ($this->isBlankValue($currentValue) && $this->isBlankValue($originalValue)) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }

    final protected function isBlankValue(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    final public function extractAcceptedNameFromNotes(mixed $taxon): ?string
    {
        if ($taxon instanceof Taxon) {
            return $taxon->proposed_accepted_name;
        }

        if (is_array($taxon)) {
            return $taxon['proposed_accepted_name'] ?? null;
        }

        return null;
    }

    final public function composeAcceptedNameNotes(?string $originalName): ?string
    {
        if ($originalName) {
            return "Original unaccepted name: {$originalName}";
        }

        return null;
    }

    final public function composeProposedAcceptedNameNotes(?string $acceptedName, mixed $existingNotes): ?string
    {
        $existingNotes = $this->taxonNormalizer->normalizeNullableString($existingNotes);

        if (! $acceptedName) {
            return $existingNotes;
        }

        $proposedPrefix = 'Proposed accepted name from WoRMS:';
        $newProposedLine = "{$proposedPrefix} {$acceptedName}";

        if ($existingNotes === null) {
            return $newProposedLine;
        }

        if (str_contains($existingNotes, $proposedPrefix)) {
            return preg_replace(
                '/'.preg_quote($proposedPrefix, '/').'.*/',
                $newProposedLine,
                $existingNotes
            );
        }

        return $newProposedLine."\n".$existingNotes;
    }

    final public function composeOriginalUnacceptedNameNotes(?string $unacceptedName): ?string
    {
        if (! $unacceptedName) {
            return null;
        }

        return "Original unaccepted name: {$unacceptedName}";
    }

    final public function buildDisplayName(array $data): ?string
    {
        $name = $data['scientificname'] ?? null;
        $authority = $data['authority'] ?? null;

        if (! $name) {
            return null;
        }

        return trim("{$name} {$authority}");
    }

    final public function formatWormsDataForForm(array $record): array
    {
        $aphiaId = $record['AphiaID'] ?? null;
        $synonyms = $aphiaId ? $this->wormsService->getSynonyms($aphiaId) : [];
        $formattedSynonyms = $this->taxonNormalizer->normalizeSynonyms($synonyms);

        $scientificName = $record['scientificname'] ?? null;
        if ($scientificName) {
            $record['Easin_id'] = $this->easinService->fetchEasinId($scientificName);
        }

        return $this->stateHelper->formatWormsDataForForm($record, $formattedSynonyms);
    }

    final public function formatChangedFieldsForNotification(array $changedFields): string
    {
        $modifiedFields = implode(', ', $changedFields);

        return "Updated fields: {$modifiedFields}. Updated At date was refreshed.";
    }

    final public function removeOldProposedAcceptedNameLine(?string $notes): ?string
    {
        if ($notes === null) {
            return null;
        }

        return trim(preg_replace('/Proposed accepted name from WoRMS: ([^(\n\r]+)/i', '', $notes)) ?: null;
    }

    final public function tryTaxonMatch(
        callable $get,
        callable $set
    ): void {
        $scientificName = $this->taxonNormalizer->normalizeNullableString($get('scientificname'));

        if ($scientificName === null) {
            Notification::make()
                ->title('No Scientific Name')
                ->body('Please enter a scientific name to perform a taxon match.')
                ->warning()
                ->send();

            return;
        }

        $results = $this->wormsService->matchTaxa($scientificName);

        // matchTaxa returns an array of arrays (one for each input name).
        // Since we only send one name, we take the first element.
        $matches = $results[0] ?? [];

        if (empty($matches)) {
            Notification::make()
                ->title('No Match Found')
                ->body("WoRMS Taxon Match could not find any suggestions for '{$scientificName}'.")
                ->danger()
                ->send();

            return;
        }

        // Filter out perfect matches if they somehow appear but searchSpecies missed them,
        // or just present the best match if it's high quality.
        $bestMatch = $matches[0] ?? null;

        if ($bestMatch) {
            $matchedName = $bestMatch['scientificname'];
            $matchScore = $bestMatch['match_type'] ?? 'unknown';

            Notification::make()
                ->title('Taxon Match Suggestion')
                ->body("Found a potential match: **{$matchedName}** ({$matchScore}).")
                ->actions([
                    Action::make('apply_match')
                        ->label('Apply Match')
                        ->color('success')
                        ->button()
                        ->close()
                        ->dispatch('applyTaxonMatch', [
                            'matchedName' => $matchedName,
                            'originalName' => $get('scientificname'),
                        ]),
                ])
                ->info()
                ->send();
        }
    }

    final public function syncWithWorms(
        callable $get,
        callable $set,
        bool $useAcceptedNameFromNotes = false
    ): void {
        $aphiaId = $this->taxonNormalizer->normalizeNullableInt($get('aphia_id'));
        $currentScientificName = $this->taxonNormalizer->normalizeNullableString($get('scientificname'));
        $scientificName = $useAcceptedNameFromNotes
            ? $this->taxonNormalizer->normalizeNullableString($get('proposed_accepted_name'))
            : $currentScientificName;
        $newNotes = $get('notes');
        $proposedAcceptedName = $get('proposed_accepted_name');

        if ($scientificName !== null) {
            $prepared = $this->prepareNameAndNotes(
                $scientificName,
                $newNotes,
                $get('kingdom'),
                $get('rank'),
                $useAcceptedNameFromNotes
            );

            $scientificName = $prepared['scientificName'];
            $newNotes = $prepared['notes'];

            if (! $useAcceptedNameFromNotes && $newNotes !== $get('notes')) {
                $set('notes', $newNotes);
            }
        }

        if ($scientificName === null && $aphiaId === null) {
            $this->sendMissingNameNotification($useAcceptedNameFromNotes);

            return;
        }

        $data = null;
        if (! $useAcceptedNameFromNotes && $aphiaId !== null) {
            $data = $this->wormsService->getRecordByAphiaID($aphiaId);
        }

        if ($data === null && $scientificName !== null) {
            $data = $this->fetchBestWormsMatch($scientificName);
        }

        if ($data === null) {
            $set('catalogue_status', Catalogue_Status::no_data_from_worms->value);
            $this->sendFetchFailedNotification($scientificName ?? (string) $aphiaId);

            return;
        }

        if (! isset($data['Easin_id'])) {
            $data['Easin_id'] = $this->easinService->fetchEasinId($data['scientificname'] ?? $scientificName);
        }

        if (($data['status'] ?? null) === 'unaccepted' && ! empty($data['valid_AphiaID'])) {
            $proposedAcceptedName = $this->getProposedAcceptedNameFromUnaccepted($data, $useAcceptedNameFromNotes) ?? $proposedAcceptedName;
        }

        if ($useAcceptedNameFromNotes) {
            $newNotes = $this->composeAcceptedNameNotes($currentScientificName);
            $proposedAcceptedName = null;
        }

        $synonyms = $this->wormsService->getSynonyms($data['AphiaID'] ?? null);
        $formattedSynonyms = $this->taxonNormalizer->normalizeSynonyms($synonyms);

        $this->updateFormStateFromWormsData(
            $get,
            $set,
            $data,
            $formattedSynonyms,
            $newNotes,
            $proposedAcceptedName
        );
    }

    private function prepareNameAndNotes(
        ?string $scientificName,
        ?string $notes,
        mixed $kingdom,
        mixed $rank,
        bool $useAcceptedNameFromNotes
    ): array {
        $temporaryTaxon = new Taxon([
            'scientificname' => $scientificName,
            'notes' => $notes,
            'kingdom' => $kingdom,
            'rank' => $rank,
        ]);

        $this->taxonNormalizer->normalize($temporaryTaxon);

        $normalizedName = $this->taxonNormalizer->normalizeNullableString($temporaryTaxon->scientificname);
        $normalizedNotes = $temporaryTaxon->notes;

        if (! $useAcceptedNameFromNotes) {
            $normalizedNotes = $this->removeOldProposedAcceptedNameLine($normalizedNotes);
        }

        return [
            'scientificName' => $normalizedName,
            'notes' => $normalizedNotes,
        ];
    }

    private function sendMissingNameNotification(bool $useAcceptedNameFromNotes): void
    {
        Notification::make()
            ->title($useAcceptedNameFromNotes ? 'No Proposed Accepted Name' : 'No Scientific Name')
            ->body($useAcceptedNameFromNotes
                ? 'No proposed accepted name from WoRMS is available.'
                : 'Please enter a scientific name to fetch data.')
            ->warning()
            ->send();
    }

    private function fetchBestWormsMatch(string $scientificName): ?array
    {
        $results = $this->wormsService->searchSpecies($scientificName);

        if (empty($results)) {
            return null;
        }

        return collect($results)->first(
            fn (array $record): bool => strcasecmp($record['scientificname'] ?? '', $scientificName) === 0,
        ) ?? $results[0];
    }

    private function sendFetchFailedNotification(string $scientificName): void
    {
        Notification::make()
            ->title('WoRMS Fetch Failed')
            ->body("Could not find a WoRMS record for '{$scientificName}'. Catalogue status updated.")
            ->danger()
            ->send();
    }

    private function getProposedAcceptedNameFromUnaccepted(array $data, bool $useAcceptedNameFromNotes): ?string
    {
        if ($useAcceptedNameFromNotes) {
            return null;
        }

        $acceptedData = $this->wormsService->getRecordByAphiaID((int) $data['valid_AphiaID']);

        return $acceptedData ? $this->buildDisplayName($acceptedData) : null;
    }

    private function updateFormStateFromWormsData(
        callable $get,
        callable $set,
        array $data,
        array $formattedSynonyms,
        ?string $newNotes,
        ?string $proposedAcceptedName
    ): void {
        $currentValues = [
            'scientificname' => $get('scientificname'),
            'authority' => $get('authority'),
            'aphia_id' => $get('aphia_id'),
            'url' => $get('url'),
            'lsid' => $get('lsid'),
            'unacceptreason' => $get('unacceptreason'),
            'kingdom' => $get('kingdom'),
            'phylum' => $get('phylum'),
            'class' => $get('class'),
            'order' => $get('order'),
            'family' => $get('family'),
            'genus' => $get('genus'),
            'rank' => $get('rank'),
            'worms_status' => $get('worms_status'),
            'catalogue_status' => $get('catalogue_status'),
            'environments' => $get('environments'),
            'is_extinct' => $get('is_extinct'),
            'synonyms_data' => $get('synonyms_data'),
            'Easin_id' => $get('Easin_id'),
            'notes' => $get('notes'),
            'proposed_accepted_name' => $get('proposed_accepted_name'),
        ];

        [$currentState, $incomingState] = $this->stateHelper->buildFetchedDataStates(
            $currentValues,
            $data,
            $formattedSynonyms,
            $newNotes,
            $proposedAcceptedName
        );

        $changedFieldLabels = $this->stateHelper->getChangedFieldLabels($currentState, $incomingState);
        $hasFetchedDataChanges = $changedFieldLabels !== [];

        if ($hasFetchedDataChanges) {
            foreach ($incomingState as $field => $value) {
                if ($field === 'synonyms_data' && is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
                $set($field, $value);
            }
            $set('fetched_at', now());

            Notification::make()
                ->title('WoRMS Data Fetched')
                ->body($this->formatChangedFieldsForNotification($changedFieldLabels))
                ->success()
                ->send();
        } else {
            $set('fetched_at', now());
            Notification::make()
                ->title('WoRMS Data Up to Date')
                ->body('No changes detected between form and WoRMS data.')
                ->info()
                ->send();
        }
    }
}
