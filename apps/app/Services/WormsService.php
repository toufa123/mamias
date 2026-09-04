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
     * Fetch synonyms by AphiaID from WoRMS.
     */
    public function getSynonyms(int $aphiaId): array
    {
        $response = $this->wormsRequest("{$this->baseUrl}/AphiaSynonymsByAphiaID/{$aphiaId}");

        return $this->processResponse($response);
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
        $data = $this->handleUnacceptedName($taxon, $data);

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
     * Handles unaccepted names by redirecting to the accepted record if available.
     */
    private function handleUnacceptedName(Taxon $taxon, array $data): array
    {
        if (($data['status'] ?? '') !== 'unaccepted' || empty($data['valid_AphiaID'])) {
            return $data;
        }

        $acceptedData = $this->getRecordByAphiaID((int) $data['valid_AphiaID']);
        if (! $acceptedData) {
            return $data;
        }

        // Store the current unaccepted name as 'original name provided' in notes
        $originalName = $data['scientificname'] ?? '';
        if ($data['authority'] ?? null) {
            $originalName .= " {$data['authority']}";
        }

        $note = "Original name provided: {$originalName} (unaccepted)";
        if ($taxon->notes) {
            if (! str_contains($taxon->notes, $note)) {
                $taxon->notes .= "\n".$note;
            }
        } else {
            $taxon->notes = $note;
        }

        return $acceptedData;
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
            $taxon->catalogue_status = Catalogue_Status::fromWormsData($data['status']);
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
