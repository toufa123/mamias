<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IntroEventRecord;
use App\Models\StagingIntroEvent;
use App\Models\SubregionRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Moves a confirmed staging row into the live tables.
 *
 * Promotion is the only step that writes to intro_event_records from this
 * import, and it is deliberately dull: it copies the values a human has
 * already confirmed. No proposing, no normalising, no WoRMS lookups — any of
 * that belongs upstream, where the admin can still see and correct it.
 *
 * Attributes are assigned one by one rather than mass-assigned. The live
 * models declare no $fillable, so a create([...]) would depend on whatever
 * global unguard state happens to be active — not something a write into the
 * catalogue should rest on.
 */
final class PromoteStagedIntroEvent
{
    /**
     * @throws RuntimeException when the row is not promotable
     */
    public function promote(StagingIntroEvent $staged, ?int $userId = null): IntroEventRecord
    {
        // Already live: hand back what was created. Makes a double-click, a
        // retried job and a re-run bulk action all harmless.
        if ($staged->promoted_intro_event_id !== null) {
            return $staged->promotedIntroEvent()->firstOrFail();
        }

        if ($staged->taxon_id === null) {
            throw new RuntimeException('Cannot promote a staged row without a resolved taxon.');
        }

        if ($staged->review_status !== StagingIntroEvent::STATUS_CONFIRMED) {
            throw new RuntimeException('Only a confirmed staged row can be promoted.');
        }

        return DB::transaction(function () use ($staged, $userId): IntroEventRecord {
            $record = new IntroEventRecord;
            $record->taxon_id = $staged->taxon_id;
            $record->first_introduction_year = $staged->first_introduction_year;
            // IntroEventRecord casts first_country to an array (a record can
            // name more than one country of first introduction). Staging keeps
            // it as plain text so the reviewer edits a field rather than a
            // JSON blob, so the shape is restored here — splitting on commas
            // exactly as the baseline importer does.
            $record->first_country = $this->toCountryList($staged->first_country);
            $record->nis_status = $staged->nis_status;
            $record->establishment_status = $staged->establishment_status;
            $record->notes = $staged->notes;

            // A human has just been through every field, which is exactly what
            // needs_review means. Promotion clears it rather than inheriting
            // the importer's uncertainty.
            $record->needs_review = false;
            $record->created_by = $userId;
            $record->updated_by = $userId;
            $record->save();

            foreach (StagingIntroEvent::SUBREGION_MAP as [$subregion, $statusColumn, $yearColumn, $nisColumn]) {
                $status = $staged->{$statusColumn};
                $year = $staged->{$yearColumn};
                $nisStatus = $staged->{$nisColumn};

                // A subregion with nothing at all is absent from the data, not
                // present-and-empty — writing a blank row would assert the
                // species was assessed there.
                if ($status === null && $year === null && $nisStatus === null) {
                    continue;
                }

                $subregionRecord = new SubregionRecord;
                $subregionRecord->intro_event_id = $record->id;
                $subregionRecord->subregion = $subregion;
                $subregionRecord->establishment_status = $status;
                $subregionRecord->nis_status = $nisStatus;
                $subregionRecord->first_arrival_year = $year;
                $subregionRecord->created_by = $userId;
                $subregionRecord->updated_by = $userId;
                $subregionRecord->save();
            }

            $staged->promoted_intro_event_id = $record->id;
            $staged->promoted_at = now();
            $staged->review_status = StagingIntroEvent::STATUS_PROMOTED;
            $staged->updated_by = $userId;
            $staged->save();

            return $record;
        });
    }

    /**
     * @return list<string>|null
     */
    private function toCountryList(?string $country): ?array
    {
        if (blank($country)) {
            return null;
        }

        $countries = array_values(array_filter(array_map('trim', explode(',', $country))));

        return $countries === [] ? null : $countries;
    }
}
