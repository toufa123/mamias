<?php

namespace App\Services;

use App\Enums\Catalogue_Status;
use App\Enums\Environment;
use App\Enums\Worms_Status;
use App\Models\Taxon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Service for interacting with the WoRMS (World Register of Marine Species) REST API.
 *
 * Provides methods to fetch taxonomic records, search species, retrieve synonyms,
 * and populate/persist taxon data from WoRMS into the local Taxon model.
 * Uses caching (24h TTL) to minimise API calls.
 *
 * The public API is deliberately non-final: this service is bound in the
 * container and swapped for a fake in tests (`app()->instance(...)`, Mockery,
 * `createMock`). Marking a public method `final` silently defeats those doubles
 * — Mockery leaves the real method in place, so the fake reaches the network —
 * and makes a subclass fake fatal outright. Internals stay final.
 */
class WormsService
{
    private const KINGDOMS = [
        2 => 'Animalia',
        6 => 'Bacteria',
        7 => 'Chromista',
        4 => 'Fungi',
        3 => 'Plantae',
        5 => 'Protozoa',
        10 => 'Viruses',
    ];

    private string $baseUrl = 'https://www.marinespecies.org/rest';

    private int $requestTimeoutSeconds = 30;

    private int $connectTimeoutSeconds = 10;

    /**
     * Fetch all marine phyla from WoRMS.
     * We start from Biota (AphiaID 1) and recursively (or selectively)
     * find children that are Phyla.
     */
    public function getPhyla(): array
    {
        return Cache::remember('worms_phyla_grouped_v2', 86400, function () {
            $groupedPhyla = [];

            foreach (self::KINGDOMS as $aphiaId => $name) {
                $phyla = [];
                $this->fetchPhylaRecursive($aphiaId, $phyla);

                if (! empty($phyla)) {
                    ksort($phyla);
                    $count = count($phyla);
                    $groupedPhyla["{$name} ({$count})"] = $phyla;
                }
            }

            ksort($groupedPhyla);

            return $groupedPhyla;
        });
    }

    /**
     * Fetch a single AphiaRecord by AphiaID.
     */
    public function getRecordByAphiaID(int $aphiaId): ?array
    {
        $response = $this->wormsRequest("{$this->baseUrl}/AphiaRecordByAphiaID/{$aphiaId}");

        if (! $response || $response->status() === 204 || $response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Search for species by name in WoRMS.
     */
    public function searchSpecies(string $search, bool $like = true, bool $marineOnly = true): array
    {
        if (strlen($search) < 3) {
            return [];
        }

        $response = $this->wormsRequest("{$this->baseUrl}/AphiaRecordsByName/".urlencode($search), [
            'like' => $like ? 'true' : 'false',
            'marine_only' => $marineOnly ? 'true' : 'false',
        ]);

        return $this->processResponse($response);
    }

    /**
     * Search for species by name in WoRMS using fuzzy matching (TAXAMATCH).
     */
    public function matchTaxa(string $name, bool $marineOnly = true): array
    {
        $response = $this->wormsRequest("{$this->baseUrl}/AphiaRecordsByMatchNames", [
            'scientificnames[]' => $name,
            'marine_only' => $marineOnly ? 'true' : 'false',
        ]);

        return $this->processResponse($response);
    }

    /**
     * Get a single record by scientific name from WoRMS.
     */
    public function getRecordByName(string $name): ?array
    {
        $records = $this->searchSpecies($name, like: false);

        return ! empty($records) ? $records[0] : null;
    }

    /**
     * Resolve a provided name to the accepted WoRMS name and AphiaID it stands
     * for, so synonyms and superseded names collapse onto one identity. Cached
     * per name for a day: imports look the same name up across chunks.
     *
     * @return array{name: ?string, aphia_id: ?int}
     */
    public function getAcceptedIdentity(string $name): array
    {
        return Cache::remember(
            'worms_v2.accepted.'.md5($name),
            now()->addDay(),
            function () use ($name): array {
                $record = $this->getRecordByName($name);

                if (! $record) {
                    return ['name' => null, 'aphia_id' => null];
                }

                $isAccepted = ($record['status'] ?? null) === 'accepted';

                return [
                    'name' => $isAccepted
                        ? ($record['scientificname'] ?? null)
                        : ($record['valid_name'] ?? $record['scientificname'] ?? null),
                    'aphia_id' => $isAccepted
                        ? ($record['AphiaID'] ?? null)
                        : ($record['valid_AphiaID'] ?? $record['AphiaID'] ?? null),
                ];
            },
        );
    }

    /**
     * Fetch synonyms by AphiaID from WoRMS.
     */
    public function getSynonyms(int $aphiaId): array
    {
        $response = $this->wormsRequest("{$this->baseUrl}/AphiaSynonymsByAphiaID/{$aphiaId}");

        return $this->processResponse($response);
    }

    /**
     * Distribution records WoRMS holds for a taxon. Cached for a month: they
     * change slowly and the name check reads them for every proposal.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDistributions(int $aphiaId): array
    {
        return Cache::remember("worms_v2.distributions.{$aphiaId}", now()->addDays(30), fn (): array => $this->processResponse(
            $this->wormsRequest("{$this->baseUrl}/AphiaDistributionsByAphiaID/{$aphiaId}"),
        ));
    }

    /**
     * The "original description" source WoRMS lists for a taxon, or null.
     *
     * A 204 (no sources) is cached like a hit; a failed call is not, so it is
     * retried on the next run.
     *
     * @return array{reference: string, doi: string|null, url: string|null}|null
     */
    public function getOriginalDescription(int $aphiaId): ?array
    {
        $sources = Cache::get($key = "worms_v2.sources.{$aphiaId}");

        if ($sources === null) {
            $response = $this->wormsRequest("{$this->baseUrl}/AphiaSourcesByAphiaID/{$aphiaId}");

            if (! $response || $response->failed()) {
                return null;
            }

            $sources = $this->processResponse($response);
            Cache::put($key, $sources, now()->addDays(30));
        }

        $source = collect($sources)->firstWhere('use', 'original description');

        if (blank($source['reference'] ?? null)) {
            return null;
        }

        return [
            'reference' => trim(strip_tags($source['reference'])),
            'doi' => $source['doi'] ?? null,
            'url' => $source['url'] ?? $source['link'] ?? null,
        ];
    }

    /**
     * Process WoRMS API response.
     */
    private function processResponse(?Response $response): array
    {
        if (! $response || $response->status() === 204 || $response->failed()) {
            return [];
        }

        $records = $response->json();

        if (isset($records['AphiaID'])) {
            return [$records];
        }

        return is_array($records) ? $records : [];
    }

    /**
     * Recursively fetch phyla starting from a given AphiaID.
     * Some kingdoms have subkingdoms or other ranks between Kingdom and Phylum.
     */
    final protected function fetchPhylaRecursive(int $aphiaId, array &$phyla, int $depth = 0): void
    {
        // Limit depth to avoid infinite loops or excessive API calls
        if ($depth > 3) {
            return;
        }

        $response = $this->wormsRequest("{$this->baseUrl}/AphiaChildrenByAphiaID/{$aphiaId}");

        if (! $response) {
            return;
        }

        if ($response->failed()) {
            return;
        }

        $children = $response->json();

        if (! is_array($children)) {
            return;
        }

        foreach ($children as $child) {
            if ($child['status'] !== 'accepted') {
                continue;
            }

            if ($child['rank'] === 'Phylum') {
                $phyla[$child['scientificname']] = $child['scientificname'];
            } elseif (in_array($child['rank'], ['Subkingdom', 'Kingdom', 'Infrakingdom', 'Superphylum'])) {
                // If it's a higher rank than Phylum, we might need to go deeper
                $this->fetchPhylaRecursive($child['AphiaID'], $phyla, $depth + 1);
            }
        }
    }

    /**
     * Populate a Taxon model with data from WoRMS.
     */
    public function populateTaxonFromWorms(Taxon $taxon, array $data): void
    {
        $this->mapTaxonFields($taxon, $data);

        // Fetch synonyms automatically without persisting yet.
        // Persistence is handled by the importer/model save lifecycle.
        $this->expandSynonyms($taxon, persist: false);

        // Normalize (LSID etc)
        app(TaxonNormalizer::class)->normalize($taxon);

        $taxon->fetched_at = now();
    }

    /**
     * Fetch and expand synonyms for a given Taxon.
     */
    public function expandSynonyms(Taxon $taxon, bool $persist = true): int
    {
        $aphiaId = $taxon->aphia_id;
        if (! $aphiaId) {
            return 0;
        }

        $synonyms = $this->getSynonyms($aphiaId);

        if (empty($synonyms)) {
            $taxon->synonyms_data = [];
            if ($persist) {
                $taxon->saveQuietly();
            }

            return 0;
        }

        // Filter only the requested fields: AphiaID, scientificname, authority, status, and unacceptreason
        $filteredSynonyms = array_map(function ($synonym) {
            return [
                'AphiaID' => $synonym['AphiaID'] ?? null,
                'scientificname' => $synonym['scientificname'] ?? null,
                'authority' => $synonym['authority'] ?? null,
                'status' => $synonym['status'] ?? null,
                'unacceptreason' => $synonym['unacceptreason'] ?? null,
            ];
        }, $synonyms);

        $taxon->synonyms_data = $filteredSynonyms;
        if ($persist) {
            $taxon->saveQuietly();
        }

        return count($filteredSynonyms);
    }

    /**
     * The accepted name WoRMS gives for a record that is not itself the
     * accepted one, or null. WoRMS states the reason in `status` (superseded
     * combination, misspelling, synonym…), so the test is the valid AphiaID,
     * not a literal "unaccepted". A difference of subgenus or nominal
     * subspecies alone does not count: MAMIAS catalogues binomials.
     *
     * @param  array<string, mixed>  $record  A WoRMS AphiaRecord.
     */
    public function acceptedNameFor(array $record): ?string
    {
        $validId = $record['valid_AphiaID'] ?? null;
        $validName = $record['valid_name'] ?? null;

        if (! $validId || $validId === ($record['AphiaID'] ?? null) || blank($validName)) {
            return null;
        }

        return TaxonNormalizer::isSameBinomial($record['scientificname'] ?? '', $validName) ? null : $validName;
    }

    /**
     * Maps WoRMS data fields to the Taxon model.
     */
    private function mapTaxonFields(Taxon $taxon, array $data): void
    {
        $taxon->aphia_id = $data['AphiaID'] ?? $taxon->aphia_id;
        $taxon->scientificname = $data['scientificname'] ?? $taxon->scientificname;
        $taxon->authority = $data['authority'] ?? $taxon->authority;
        $taxon->kingdom = $data['kingdom'] ?? $taxon->kingdom;
        $taxon->phylum = $data['phylum'] ?? $taxon->phylum;
        $taxon->class = $data['class'] ?? $taxon->class;
        $taxon->order = $data['order'] ?? $taxon->order;
        $taxon->family = $data['family'] ?? $taxon->family;
        $taxon->genus = $data['genus'] ?? $taxon->genus;
        $taxon->rank = $data['rank'] ?? $taxon->rank;
        $statusValue = $data['status'] ?? '';
        if ($statusValue instanceof Worms_Status) {
            $taxon->worms_status = $statusValue;
        } else {
            $taxon->worms_status = Worms_Status::tryFrom((string) $statusValue) ?? $taxon->worms_status;
        }
        $taxon->unacceptreason = $data['unacceptreason'] ?? $taxon->unacceptreason;
        $taxon->is_extinct = ! empty($data['isExtinct']);
        $taxon->url = $data['url'] ?? $taxon->url;

        if (isset($data['status'])) {
            $taxon->catalogue_status = Catalogue_Status::fromWormsData($data['status'], $data);
        }

        // A name the team decided not to follow is not proposed again; a
        // different one later is.
        $proposed = $this->acceptedNameFor($data);
        $proposed = $proposed === $taxon->dismissed_accepted_name ? null : $proposed;

        $taxon->proposed_accepted_name = $proposed;
        $taxon->proposed_accepted_aphia_id = $proposed ? ($data['valid_AphiaID'] ?? null) : null;

        if (! $proposed) {
            $taxon->name_change_confidence = null;
            $taxon->name_change_reasons = null;
        }

        $taxon->environments = Environment::fromWormsData($data);
    }

    /**
     * Send an HTTP GET request to the WoRMS API with retry and timeout settings.
     */
    final protected function wormsRequest(string $url, array $query = []): ?Response
    {
        return Http::connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->requestTimeoutSeconds)
            ->retry(2, 200, throw: false)
            ->get($url, $query);
    }
}
