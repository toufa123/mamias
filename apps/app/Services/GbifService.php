<?php

namespace App\Services;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Service for querying the GBIF (Global Biodiversity Information Facility) Species API.
 *
 * Used as a fallback when a species cannot be found in WoRMS. Provides fuzzy name
 * matching and maps the GBIF response to the taxon form field structure.
 */
class GbifService
{
    private string $baseUrl = 'https://api.gbif.org/v1';

    private int $requestTimeoutSeconds = 15;

    /**
     * Fuzzy-match a scientific name against GBIF.
     *
     * Returns the best match record or null when matchType is NONE.
     *
     * @return array<string, mixed>|null
     */
    public function matchSpecies(string $name): ?array
    {
        return Cache::remember('gbif_match_'.md5($name), 86400, function () use ($name) {
            try {
                $response = Http::timeout($this->requestTimeoutSeconds)
                    ->get("{$this->baseUrl}/species/match", [
                        'name' => $name,
                        'verbose' => 'true',
                    ]);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();

                if (($data['matchType'] ?? 'NONE') === 'NONE') {
                    return null;
                }

                return $data;
            } catch (\Exception $e) {
                logger()->error('GBIF match request failed', [
                    'name' => $name,
                    'message' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Map a GBIF match result to taxon form field keys.
     *
     * Only fills fields that GBIF provides; WoRMS-specific fields (aphia_id, url, lsid)
     * are left for the caller to handle.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public function formatForForm(array $record): array
    {
        $canonicalName = $record['canonicalName'] ?? null;
        $fullScientificName = $record['scientificName'] ?? null;

        $authority = null;
        if ($canonicalName && $fullScientificName && str_starts_with($fullScientificName, $canonicalName)) {
            $stripped = trim(substr($fullScientificName, strlen($canonicalName)));
            if ($stripped !== '') {
                $authority = $stripped;
            }
        }

        return [
            'scientificname' => $canonicalName ?? $fullScientificName,
            'authority' => $authority,
            'kingdom' => $record['kingdom'] ?? null,
            'phylum' => $record['phylum'] ?? null,
            'class' => $record['class'] ?? null,
            'order' => $record['order'] ?? null,
            'family' => $record['family'] ?? null,
            'genus' => $record['genus'] ?? null,
            'rank' => isset($record['rank']) ? strtolower($record['rank']) : null,
        ];
    }

    /**
     * Attempt a GBIF species match and notify the user with an apply action.
     *
     * Mirrors the pattern of TaxonService::tryTaxonMatch.
     */
    public function tryGbifMatch(callable $get): void
    {
        $scientificName = $get('scientificname');

        if (! $scientificName) {
            Notification::make()
                ->title('No Scientific Name')
                ->body('Please enter a scientific name to search GBIF.')
                ->warning()
                ->send();

            return;
        }

        $match = $this->matchSpecies($scientificName);

        if (! $match) {
            Notification::make()
                ->title('No Match Found in GBIF')
                ->body("GBIF could not find any match for '{$scientificName}'.")
                ->danger()
                ->send();

            return;
        }

        $formData = $this->formatForForm($match);
        $confidence = $match['confidence'] ?? '?';
        $matchType = strtolower($match['matchType'] ?? 'unknown');
        $matchedName = $formData['scientificname'];

        Notification::make()
            ->title('GBIF Match Found')
            ->body("Found: **{$matchedName}** ({$matchType} match, {$confidence}% confidence). Apply to fill in the taxonomic classification fields.")
            ->actions([
                Action::make('apply_gbif_match')
                    ->label('Apply GBIF Data')
                    ->color('success')
                    ->button()
                    ->close()
                    ->dispatch('applyGbifMatch', ['gbifData' => $formData]),
            ])
            ->info()
            ->persistent()
            ->send();
    }
}
