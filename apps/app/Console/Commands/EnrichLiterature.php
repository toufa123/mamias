<?php

namespace App\Console\Commands;

use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Models\Literature;
use App\Models\Taxon;
use App\Services\DoiMetadataService;
use App\Services\WormsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('literature:enrich
    {--limit=200 : Maximum literatures checked per Crossref step}
    {--search : Suggest DOIs for references without one}
    {--descriptions : Link each taxon to its WoRMS original description}')]
#[Description('Sync literature metadata and retractions from Crossref, suggest missing DOIs, and link WoRMS original descriptions.')]
class EnrichLiterature extends Command
{
    /** How long a DOI sync stays fresh before Crossref is asked again. */
    private const RESYNC_DAYS = 30;

    public function handle(DoiMetadataService $crossref, WormsService $worms): int
    {
        $this->syncDois($crossref);

        if ($this->option('search')) {
            $this->suggestDois($crossref);
        }

        if ($this->option('descriptions')) {
            $this->linkOriginalDescriptions($crossref, $worms);
        }

        return self::SUCCESS;
    }

    /**
     * Fill a missing year and refresh the retraction flag. Curated reference
     * strings are never overwritten.
     */
    private function syncDois(DoiMetadataService $crossref): void
    {
        $synced = 0;
        $retracted = [];

        Literature::query()
            ->whereNotNull('doi')
            ->where(fn ($q) => $q->whereNull('crossref_checked_at')
                ->orWhere('crossref_checked_at', '<', now()->subDays(self::RESYNC_DAYS)))
            ->limit((int) $this->option('limit'))
            ->get()
            ->each(function (Literature $literature) use ($crossref, &$synced, &$retracted) {
                $metadata = $crossref->fetchFromCrossref($literature->doi);

                // An unknown DOI is stamped too, so it is not retried every run.
                $literature->crossref_checked_at = now();

                if ($metadata) {
                    $literature->year ??= $metadata['year'];

                    if ($metadata['is_retracted'] && ! $literature->is_retracted) {
                        $retracted[] = "{$literature->code} ({$literature->doi})";
                    }

                    $literature->is_retracted = $metadata['is_retracted'];
                    $synced++;
                } else {
                    $this->warn("Crossref does not know {$literature->doi} ({$literature->code}).");
                }

                $literature->save();
            });

        $this->info("Synced {$synced} DOI(s) with Crossref.");

        foreach ($retracted as $reference) {
            $this->error("Newly retracted: {$reference}");
        }
    }

    /**
     * Store a candidate DOI for curator review; never write it to `doi` directly.
     */
    private function suggestDois(DoiMetadataService $crossref): void
    {
        $suggested = 0;

        Literature::query()
            ->whereNull('doi')
            ->whereNull('suggested_doi')
            ->whereNull('crossref_checked_at')
            ->whereNotNull('full_ref')
            ->limit((int) $this->option('limit'))
            ->get()
            ->each(function (Literature $literature) use ($crossref, &$suggested) {
                $doi = $crossref->searchDoi($literature->full_ref);

                if ($doi && ! Literature::where('doi', $doi)->exists()) {
                    $literature->suggested_doi = $doi;
                    $suggested++;
                }

                $literature->crossref_checked_at = now();
                $literature->save();
            });

        $this->info("Suggested {$suggested} DOI(s) for review.");
    }

    /**
     * WoRMS responses are cached for 30 days, so re-checking taxa that have no
     * listed description is cheap after the first run.
     */
    private function linkOriginalDescriptions(DoiMetadataService $crossref, WormsService $worms): void
    {
        $linked = 0;

        Taxon::query()
            ->whereNotNull('aphia_id')
            ->whereNull('original_description_id')
            ->chunkById(100, function ($taxa) use ($crossref, $worms, &$linked) {
                foreach ($taxa as $taxon) {
                    $source = $worms->getOriginalDescription($taxon->aphia_id);

                    if (! $source) {
                        continue;
                    }

                    $taxon->originalDescription()->associate($this->literatureFor($source, $taxon, $crossref));
                    $taxon->save();
                    $linked++;
                }
            });

        $this->info("Linked {$linked} original description(s) from WoRMS.");
    }

    /**
     * Reuse the literature a DOI or identical reference already points to,
     * otherwise create it approved: WoRMS is the curated source here.
     *
     * @param  array{reference: string, doi: string|null, url: string|null}  $source
     */
    private function literatureFor(array $source, Taxon $taxon, DoiMetadataService $crossref): Literature
    {
        $doi = DoiMetadataService::normalize($source['doi']);

        $existing = $doi
            ? Literature::where('doi', $doi)->first()
            : Literature::where('full_ref', $source['reference'])->first();

        if ($existing) {
            return $existing;
        }

        $metadata = $doi ? $crossref->fetchFromCrossref($doi) : null;

        return Literature::create([
            'doi' => $doi,
            'full_ref' => $metadata['full_ref'] ?? $source['reference'],
            'short_ref' => $metadata['short_ref'] ?? $this->shortRef($taxon, $source['reference']),
            // ponytail: non-DOI WoRMS sources default to article; curators fix books/monographs by hand.
            'type' => $metadata['type'] ?? LiteratureType::ARTICLE,
            'link' => $metadata['link'] ?? $source['url'],
            'year' => $metadata['year'] ?? $this->yearFrom($source['reference']),
            'is_retracted' => $metadata['is_retracted'] ?? false,
            'crossref_checked_at' => $metadata ? now() : null,
            'status' => LiteratureStatus::APPROVED,
        ]);
    }

    /**
     * The authority of a name cites its original description, "(Linnaeus, 1758)"
     * reads "Linnaeus, 1758".
     */
    private function shortRef(Taxon $taxon, string $reference): string
    {
        $authority = trim((string) $taxon->authority, " ()\t\n");

        return $authority !== ''
            ? Str::limit($authority, 250)
            : Str::limit(Str::before($reference, ',').', '.($this->yearFrom($reference) ?? 'n.d.'), 250);
    }

    private function yearFrom(string $reference): ?int
    {
        return preg_match('/\b(1[5-9]\d{2}|20\d{2})\b/', $reference, $m) ? (int) $m[1] : null;
    }
}
