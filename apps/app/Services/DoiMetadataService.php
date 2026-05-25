<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LiteratureType;
use Illuminate\Support\Facades\Http;

class DoiMetadataService
{
    /**
     * Fetch metadata from Crossref for a given DOI.
     */
    final public function fetchFromCrossref(string $doi): ?array
    {
        $response = Http::get('https://api.crossref.org/works/'.rawurlencode($doi));

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json('message');

        if (empty($data)) {
            return null;
        }

        return [
            'full_ref' => $this->formatFullReference($data),
            'short_ref' => $this->formatShortReference($data),
            'type' => $this->mapType($data['type'] ?? ''),
            'link' => $data['URL'] ?? "https://doi.org/{$doi}",
        ];
    }

    /**
     * Map Crossref types to LiteratureType Enum.
     */
    final protected function mapType(string $crossrefType): LiteratureType
    {
        return match ($crossrefType) {
            'journal-article' => LiteratureType::ARTICLE,
            'report' => LiteratureType::TECHNICAL_REPORT,
            'dissertation' => LiteratureType::THESIS,
            'book' => LiteratureType::BOOK,
            'proceedings-article' => LiteratureType::CONFERENCE_PROCEEDINGS,
            default => LiteratureType::ARTICLE,
        };
    }

    /**
     * Format a full reference string from Crossref data.
     */
    final protected function formatFullReference(array $data): string
    {
        $authors = collect($data['author'] ?? [])
            ->map(fn ($a) => trim(($a['family'] ?? '').', '.($a['given'] ?? ''), ', '))
            ->filter()
            ->implode('; ') ?: 'Unknown Authors';

        $title = $data['title'][0] ?? 'No Title';
        $year = $this->getYear($data);

        $ref = "{$authors} ({$year}). {$title}.";

        if ($journal = ($data['container-title'][0] ?? null)) {
            $ref .= " {$journal}";
            if ($volume = ($data['volume'] ?? null)) {
                $ref .= ", {$volume}";
            }
            if ($issue = ($data['issue'] ?? null)) {
                $ref .= "({$issue})";
            }
            if ($pages = ($data['page'] ?? null)) {
                $ref .= ", {$pages}";
            }
            $ref .= '.';
        }

        return $ref;
    }

    /**
     * Format a short reference string (e.g., Smith et al., 2024).
     */
    final protected function formatShortReference(array $data): string
    {
        $authors = $data['author'] ?? [];
        $firstAuthor = $authors[0]['family'] ?? 'Unknown';
        $year = $this->getYear($data);

        if (count($authors) > 1) {
            return "{$firstAuthor} et al., {$year}";
        }

        return "{$firstAuthor}, {$year}";
    }

    /**
     * Extract the publication year from Crossref data.
     */
    private function getYear(array $data): string
    {
        if (isset($data['published-print']['date-parts'][0][0])) {
            return (string) $data['published-print']['date-parts'][0][0];
        }

        if (isset($data['published-online']['date-parts'][0][0])) {
            return (string) $data['published-online']['date-parts'][0][0];
        }

        return 'n.d.';
    }
}
