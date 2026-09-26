<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LiteratureType;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Service for fetching bibliographic metadata from Crossref via DOI.
 *
 * Retrieves full and short reference strings, literature type mapping,
 * year, retraction state and links; also searches DOIs for free-text
 * references and serves BibTeX. Successful lookups are cached.
 */
class DoiMetadataService
{
    /**
     * Reduce any pasted DOI form (URL, "doi:" prefix, mixed case) to the bare
     * lowercase DOI. DOIs are case-insensitive, so this is the stored form.
     */
    public static function normalize(?string $doi): ?string
    {
        $doi = strtolower(trim((string) preg_replace('~^\s*(https?://(dx\.)?doi\.org/|doi:\s*)~i', '', (string) $doi)));

        return $doi === '' ? null : $doi;
    }

    /**
     * Fetch metadata from Crossref for a given DOI.
     *
     * @return array{full_ref: string, short_ref: string, type: LiteratureType, link: string, year: int|null, is_retracted: bool}|null
     */
    final public function fetchFromCrossref(string $doi): ?array
    {
        $doi = self::normalize($doi);
        $data = $doi ? $this->fetchWork($doi) : null;

        if (empty($data)) {
            return null;
        }

        $year = $this->getYear($data);

        return [
            'full_ref' => $this->formatFullReference($data),
            'short_ref' => $this->formatShortReference($data),
            'type' => $this->mapType($data['type'] ?? ''),
            'link' => $data['URL'] ?? "https://doi.org/{$doi}",
            'year' => $year === 'n.d.' ? null : (int) $year,
            'is_retracted' => collect($data['updated-by'] ?? [])->contains('type', 'retraction'),
        ];
    }

    /**
     * The raw Crossref work record. Only successful lookups are cached:
     * Cache::remember() never stores null, so a failed call is retried next time.
     */
    final public function fetchWork(string $doi): ?array
    {
        return Cache::remember('crossref_work_'.md5($doi), now()->addDay(), function () use ($doi) {
            $response = $this->crossref()->get('https://api.crossref.org/works/'.rawurlencode($doi));

            return $response->successful() ? ($response->json('message') ?: null) : null;
        });
    }

    /**
     * Find the DOI of a free-text reference. Crossref always returns a best
     * guess, so it only counts when nearly all words of the candidate's title
     * appear in the reference; titles under four words match too easily.
     */
    final public function searchDoi(string $reference): ?string
    {
        $response = $this->crossref()->get('https://api.crossref.org/works', [
            'query.bibliographic' => $reference,
            'rows' => 1,
            'select' => 'DOI,title',
        ]);

        $item = $response->successful() ? $response->json('message.items.0') : null;
        $titleWords = self::words($item['title'][0] ?? '');

        if (count($titleWords) < 4) {
            return null;
        }

        $overlap = count(array_intersect($titleWords, self::words($reference))) / count($titleWords);

        return $overlap >= 0.9 ? self::normalize($item['DOI'] ?? null) : null;
    }

    /**
     * BibTeX entry for a DOI, via DOI content negotiation.
     */
    final public function bibtex(string $doi): ?string
    {
        return Cache::remember('doi_bibtex_'.md5($doi), now()->addWeek(), function () use ($doi) {
            $response = $this->crossref()
                ->accept('application/x-bibtex')
                ->get('https://doi.org/'.$doi);

            return $response->successful() ? (trim($response->body()) ?: null) : null;
        });
    }

    /**
     * Crossref serves identified clients from its "polite" pool.
     */
    private function crossref(): PendingRequest
    {
        $mailto = config('services.crossref.mailto');

        return Http::timeout(10)
            ->retry(2, 300, fn ($e) => $e instanceof ConnectionException, throw: false)
            ->withUserAgent('MAMIAS/1.0'.($mailto ? " (mailto:{$mailto})" : ''));
    }

    /**
     * Distinct lowercase words of three letters or more, so punctuation,
     * possessives ("Union’s") and markup do not break the comparison.
     *
     * @return list<string>
     */
    private static function words(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(strip_tags($text)), flags: PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter($words, fn (string $word) => mb_strlen($word) >= 3)));
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

        if (isset($data['issued']['date-parts'][0][0])) {
            return (string) $data['issued']['date-parts'][0][0];
        }

        return 'n.d.';
    }
}
