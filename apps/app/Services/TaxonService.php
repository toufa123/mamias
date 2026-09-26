<?php

namespace App\Services;

use App\Enums\Catalogue_Status;
use App\Filament\Resources\Taxons\TaxonResource;
use App\Models\IntroEventRecord;
use App\Models\NisSuggestion;
use App\Models\Taxon;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

/**
 * High-level service for managing Taxon records and synchronising with WoRMS.
 *
 * Orchestrates WoRMS data fetching, normalisation, EASIN ID resolution,
 * and form-state comparison for taxon resources in the Filament panel.
 */
class TaxonService
{
    public function __construct(
        private readonly WormsService $wormsService,
        private readonly TaxonNormalizer $taxonNormalizer,
        private readonly TaxonStateHelper $stateHelper,
        private readonly EasinService $easinService,
        private readonly AcceptedNameConfidence $nameConfidence,
    ) {}

    /**
     * @param  (callable(Taxon): void)|null  $onProgress  Called once per processed taxon, on every
     *                                                    outcome (not found, unchanged, updated), with that taxon.
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
                    $onProgress($taxon);
                }

                continue;
            }

            $this->wormsService->populateTaxonFromWorms($taxon, $wormsData);

            if ($taxon->proposed_accepted_name && ($taxon->isDirty('proposed_accepted_name') || $taxon->name_change_confidence === null)) {
                $this->assessProposedName($taxon, $wormsData);
            }

            if (! $taxon->Easin_id) {
                $taxon->Easin_id = $this->easinService->fetchEasinId($taxon->scientificname);
            }

            if (! $this->hasMeaningfulChanges($taxon)) {
                $taxon->save();

                if ($onProgress) {
                    $onProgress($taxon);
                }

                continue;
            }

            $taxon->save();
            $updated++;

            if ($onProgress) {
                $onProgress($taxon);
            }
        }

        return [
            'updated' => $updated,
            'missing_aphia_id' => $missingAphiaId,
            'not_found' => $notFound,
        ];
    }

    /**
     * Scores how sure we can be that the accepted name WoRMS proposes for this
     * taxon is the same species (see AcceptedNameConfidence). Clears the score
     * when nothing is proposed.
     *
     * @param  array<string, mixed>  $record  The WoRMS record of the catalogued name.
     */
    final public function assessProposedName(Taxon $taxon, array $record): void
    {
        $accepted = $taxon->proposed_accepted_aphia_id
            ? $this->wormsService->getRecordByAphiaID($taxon->proposed_accepted_aphia_id)
            : null;

        if (! $accepted) {
            $taxon->name_change_confidence = null;
            $taxon->name_change_reasons = null;

            return;
        }

        $assessment = $this->nameConfidence->assess($record, $accepted);

        $taxon->name_change_confidence = $assessment['score'];
        $taxon->name_change_reasons = $assessment['reasons'];
    }

    /**
     * Moves a taxon to the accepted name WoRMS gives for it, and returns the
     * taxon that now holds its records.
     *
     * Each introduction event first keeps the name it was recorded under
     * (`verbatim_name`). Then, if the accepted taxon is already catalogued,
     * events, suggestions and the original description move to it and this
     * taxon is trashed; otherwise this taxon is renamed in place, so every
     * link follows. The move is logged on the resulting taxon with the score,
     * the reasons, the curator's note and what undoLastMove() needs.
     *
     * @throws RuntimeException When WoRMS gives no other accepted name.
     */
    final public function moveToAcceptedName(Taxon $taxon, ?string $note = null): Taxon
    {
        $record = $taxon->aphia_id ? $this->wormsService->getRecordByAphiaID($taxon->aphia_id) : null;
        $accepted = $record && $this->wormsService->acceptedNameFor($record) !== null
            ? $this->wormsService->getRecordByAphiaID((int) $record['valid_AphiaID'])
            : null;

        if (! $accepted) {
            throw new RuntimeException("WoRMS gives no other accepted name for {$taxon->scientificname}.");
        }

        $oldName = $taxon->scientificname;
        $reason = $taxon->worms_status?->getLabel() ?? 'not accepted';

        return DB::transaction(function () use ($taxon, $accepted, $oldName, $reason, $note): Taxon {
            $decision = [
                'from' => $oldName,
                'confidence' => $taxon->name_change_confidence,
                'reasons' => $taxon->name_change_reasons,
                'note' => filled($note) ? trim($note) : null,
                'reviewer_id' => $taxon->name_reviewer_id,
            ];

            $taxon->introEvents()->withTrashed()->whereNull('verbatim_name')->update(['verbatim_name' => $oldName]);

            $target = $this->catalogued($accepted, exceptId: $taxon->getKey());

            if (! $target) {
                $before = Arr::only($taxon->getAttributes(), self::MOVE_SNAPSHOT);

                $this->wormsService->populateTaxonFromWorms($taxon, $accepted);
                $taxon->notes = $this->appendNote($taxon->notes, "Previously catalogued as {$oldName} ({$reason}).");
                $taxon->name_reviewer_id = null;
                $taxon->save();

                $this->logMove($taxon, $decision + ['to' => $taxon->scientificname, 'undo' => ['renamed' => $before]]);

                return $taxon;
            }

            $wasTrashed = $target->trashed();

            if ($wasTrashed) {
                $target->restore();
            }

            $undo = [
                'merged_taxon_id' => $taxon->getKey(),
                'target_was_trashed' => $wasTrashed,
                'target_before' => Arr::only($target->getAttributes(), ['original_description_id', 'notes']),
                'merged_before' => Arr::only($taxon->getAttributes(), ['notes', 'name_reviewer_id']),
                'intro_event_ids' => $taxon->introEvents()->withTrashed()->pluck('id')->all(),
                'nis_suggestion_ids' => $taxon->nisSuggestions()->withTrashed()->pluck('id')->all(),
            ];

            $taxon->introEvents()->withTrashed()->update(['taxon_id' => $target->getKey()]);
            $taxon->nisSuggestions()->withTrashed()->update(['taxon_id' => $target->getKey()]);
            $target->original_description_id ??= $taxon->original_description_id;
            $target->notes = $this->appendNote($target->notes, "Records of {$oldName} ({$reason}) merged in.");
            $target->save();

            $taxon->notes = $this->appendNote($taxon->notes, "Merged into {$target->scientificname}.");
            $taxon->name_reviewer_id = null;
            $taxon->save();
            $taxon->delete();

            $this->logMove($target, $decision + ['to' => $target->scientificname, 'undo' => $undo]);

            return $target;
        });
    }

    /**
     * The catalogued taxon that already holds the proposed accepted name, if
     * any: moving would then merge into it. Read from the stored proposal,
     * so it needs no WoRMS call and can run while a dialog renders.
     */
    final public function acceptedInCatalogue(Taxon $taxon): ?Taxon
    {
        if (! $taxon->proposed_accepted_name) {
            return null;
        }

        return ($taxon->proposed_accepted_aphia_id
            ? Taxon::where('aphia_id', $taxon->proposed_accepted_aphia_id)->whereKeyNot($taxon->getKey())->first()
            : null) ?? Taxon::findDuplicateOf($taxon->proposed_accepted_name, $taxon->getKey());
    }

    /**
     * The team keeps the catalogued name rather than follow WoRMS. The
     * dismissed name is remembered so the monthly check does not propose it
     * again; a different accepted name later is proposed as usual.
     */
    final public function keepCurrentName(Taxon $taxon, string $reason): void
    {
        $dismissed = $taxon->proposed_accepted_name;

        $taxon->fill([
            'dismissed_accepted_name' => $dismissed,
            'dismissed_reason' => trim($reason),
            'proposed_accepted_name' => null,
            'proposed_accepted_aphia_id' => null,
            'name_change_confidence' => null,
            'name_change_reasons' => null,
            'name_reviewer_id' => null,
        ])->save();

        activity()
            ->performedOn($taxon)
            ->withProperties(['dismissed' => $dismissed, 'reason' => trim($reason)])
            ->log('kept current name');
    }

    /**
     * Asks a scientist to decide on the proposed name: assigns them, opens
     * the discussion with the question, and notifies them in the panel.
     */
    final public function sendForNameReview(Taxon $taxon, User $reviewer, User $sender, ?string $message = null): void
    {
        $taxon->update(['name_reviewer_id' => $reviewer->getKey()]);

        $question = "Should {$taxon->scientificname} move to {$taxon->proposed_accepted_name}? "
            ."Confidence {$taxon->name_change_confidence}% (".AcceptedNameConfidence::band($taxon->name_change_confidence)['label'].').'
            .(filled($message) ? "\n\n".trim($message) : '');

        $taxon->comment(e($question), $sender);

        // After the question, so it reaches them once (below), not also as a
        // new message; later replies then reach them as a participant.
        $taxon->subscribe($reviewer);

        Notification::make()
            ->title('Name review requested')
            ->body("{$sender->name} asks you to decide whether {$taxon->scientificname} should move to {$taxon->proposed_accepted_name}.")
            ->icon('tabler-arrow-right-circle')
            ->actions([
                Action::make('open')
                    ->label('Open the species')
                    ->url(TaxonResource::getUrl('edit', ['record' => $taxon])),
            ])
            ->sendToDatabase($reviewer);
    }

    /**
     * The latest move logged on this taxon that has not been undone, or null.
     */
    final public function lastUndoableMove(Taxon $taxon): ?Activity
    {
        $latest = Activity::query()
            ->where('subject_type', $taxon->getMorphClass())
            ->where('subject_id', $taxon->getKey())
            ->whereIn('description', ['moved to accepted name', 'move undone'])
            ->latest('id')
            ->first();

        return $latest?->description === 'moved to accepted name' ? $latest : null;
    }

    /**
     * Reverses the latest move on this taxon: restores the previous name and
     * classification, or, after a merge, sends the records back to the
     * restored taxon they came from. Events keep their recorded name.
     * Returns the taxon holding the records afterwards.
     *
     * @throws RuntimeException When there is nothing to undo, or the old name is taken.
     */
    final public function undoLastMove(Taxon $taxon): Taxon
    {
        $move = $this->lastUndoableMove($taxon) ?? throw new RuntimeException("No move to undo on {$taxon->scientificname}.");
        $undo = $move->properties['undo'];

        return DB::transaction(function () use ($taxon, $move, $undo): Taxon {
            if (isset($undo['renamed'])) {
                $before = $undo['renamed'];

                if (Taxon::findDuplicateOf($before['scientificname'], $taxon->getKey())) {
                    throw new RuntimeException("{$before['scientificname']} is now used by another species; undo it by hand.");
                }

                // Raw values, as snapshotted: going through the casts would
                // encode the JSON columns a second time.
                $taxon->setRawAttributes(array_merge($taxon->getAttributes(), $before));
                $taxon->save();
                $result = $taxon;
            } else {
                $merged = Taxon::withTrashed()->findOrFail($undo['merged_taxon_id']);
                $merged->restore();
                $merged->forceFill($undo['merged_before'])->save();

                IntroEventRecord::withTrashed()->whereKey($undo['intro_event_ids'])->update(['taxon_id' => $merged->getKey()]);
                NisSuggestion::withTrashed()->whereKey($undo['nis_suggestion_ids'])->update(['taxon_id' => $merged->getKey()]);

                $taxon->forceFill($undo['target_before'])->save();

                if ($undo['target_was_trashed']) {
                    $taxon->delete();
                }

                $result = $merged;
            }

            activity()->performedOn($taxon)->withProperties(['undoes' => $move->getKey()])->log('move undone');

            return $result;
        });
    }

    /**
     * Fields a rename overwrites, kept so it can be undone.
     */
    private const MOVE_SNAPSHOT = [
        'aphia_id', 'url', 'scientificname', 'authority', 'worms_status', 'catalogue_status', 'unacceptreason',
        'rank', 'kingdom', 'phylum', 'class', 'order', 'family', 'genus', 'lsid', 'is_extinct', 'environments',
        'synonyms_data', 'fetched_at', 'notes', 'proposed_accepted_name', 'proposed_accepted_aphia_id',
        'name_change_confidence', 'name_change_reasons', 'name_reviewer_id',
    ];

    /**
     * The catalogued taxon for this WoRMS record, other than $exceptId, trashed or not.
     *
     * @param  array<string, mixed>  $record
     */
    private function catalogued(array $record, int $exceptId): ?Taxon
    {
        return Taxon::withTrashed()->where('aphia_id', $record['AphiaID'])->whereKeyNot($exceptId)->first()
            ?? Taxon::findDuplicateOf($record['scientificname'], $exceptId);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function logMove(Taxon $taxon, array $properties): void
    {
        activity()->performedOn($taxon)->withProperties($properties)->log('moved to accepted name');
    }

    private function appendNote(?string $notes, string $line): string
    {
        return filled($notes) ? rtrim($notes)."\n".$line : $line;
    }

    /**
     * Determine whether a taxon record has meaningful changes beyond timestamp updates.
     * Ignores blank-to-blank transitions for the `unacceptreason` field.
     */
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

    /**
     * Check if a value is null or a blank/whitespace-only string.
     */
    final protected function isBlankValue(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * Extract the proposed accepted name from a taxon record or array.
     */
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

    /**
     * Compose a notes string indicating the original unaccepted name.
     */
    final public function composeAcceptedNameNotes(?string $originalName): ?string
    {
        if ($originalName) {
            return "Original unaccepted name: {$originalName}";
        }

        return null;
    }

    /**
     * Compose or update the "Proposed accepted name from WoRMS" line in the notes.
     * Replaces an existing proposal line if present, or appends a new one.
     */
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

    /**
     * Compose a notes string for an original unaccepted name.
     */
    final public function composeOriginalUnacceptedNameNotes(?string $unacceptedName): ?string
    {
        if (! $unacceptedName) {
            return null;
        }

        return "Original unaccepted name: {$unacceptedName}";
    }

    /**
     * Build a display name combining scientific name and authority.
     */
    final public function buildDisplayName(array $data): ?string
    {
        $name = $data['scientificname'] ?? null;
        $authority = $data['authority'] ?? null;

        if (! $name) {
            return null;
        }

        return trim("{$name} {$authority}");
    }

    /**
     * Format a WoRMS record array for display in a Filament form.
     * Fetches synonyms and EASIN ID for the record.
     */
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

    /**
     * Format a notification message listing the changed fields after a WoRMS fetch.
     */
    final public function formatChangedFieldsForNotification(array $changedFields): string
    {
        $modifiedFields = implode(', ', $changedFields);

        return "Updated fields: {$modifiedFields}. Updated At date was refreshed.";
    }

    /**
     * Remove any existing "Proposed accepted name from WoRMS" line from notes.
     */
    final public function removeOldProposedAcceptedNameLine(?string $notes): ?string
    {
        if ($notes === null) {
            return null;
        }

        return trim(preg_replace('/Proposed accepted name from WoRMS: ([^(\n\r]+)/i', '', $notes)) ?: null;
    }

    /**
     * Attempt a WoRMS Taxon Match against a scientific name and notify the user
     * with a suggestion action to apply the match.
     */
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

    /**
     * Synchronise a taxon form's fields with data fetched from WoRMS.
     * Compares current form state with incoming WoRMS data and updates
     * only fields that have changed, with notification feedback.
     */
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

        if ($this->wormsService->acceptedNameFor($data) !== null) {
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
