<?php

namespace App\Services;

use App\Enums\Environment;
use App\Models\Taxon;

class TaxonNormalizer
{
    /**
     * Normalize the scientific name and LSID of a Taxon record.
     */
    final public function normalize(Taxon $taxon): void
    {
        $this->normalizeScientificName($taxon);
        $this->normalizeLsid($taxon);
    }

    final public function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        $normalizedValue = trim((string) $value);

        return $normalizedValue !== '' ? $normalizedValue : null;
    }

    final public function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || (is_string($value) && $value === '')) {
            return null;
        }

        return (int) $value;
    }

    final public function normalizeEnvironments(mixed $environments): array
    {
        if (is_string($environments)) {
            $decoded = json_decode($environments, true);
            $environments = is_array($decoded) ? $decoded : [$environments];
        }

        if (! is_array($environments)) {
            return [];
        }

        return collect($environments)
            ->map(function (mixed $environment): ?string {
                if ($environment instanceof Environment) {
                    return $environment->value;
                }

                if (! is_string($environment)) {
                    return null;
                }

                return Environment::fromLabelOrValue($environment)?->value ?? $this->normalizeNullableString($environment);
            })
            ->filter(fn (?string $environment): bool => $environment !== null)
            ->sort()
            ->values()
            ->all();
    }

    final public function normalizeSynonyms(mixed $synonyms): array
    {
        if (is_string($synonyms)) {
            $decoded = json_decode($synonyms, true);
            $synonyms = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($synonyms)) {
            return [];
        }

        return collect($synonyms)
            ->filter(fn (mixed $synonym): bool => is_array($synonym))
            ->map(fn (array $synonym): array => [
                'AphiaID' => $this->normalizeNullableInt($synonym['AphiaID'] ?? null),
                'scientificname' => $this->normalizeNullableString($synonym['scientificname'] ?? null),
                'authority' => $this->normalizeNullableString($synonym['authority'] ?? null),
                'status' => $this->normalizeNullableString($synonym['status'] ?? null),
                'unacceptreason' => $this->normalizeNullableString($synonym['unacceptreason'] ?? null),
            ])
            ->sortBy(fn (array $synonym): string => sprintf('%020d|%s', (int) ($synonym['AphiaID'] ?? 0), $synonym['scientificname'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * Sanitize encoding artifacts commonly produced by Excel/CSV exports.
     *
     * Handles: ÿ (0xFF mojibake), BOM, Windows-1252 control chars,
     * non-breaking spaces, and other invisible Unicode whitespace.
     */
    final public function sanitizeEncodingArtifacts(string $value): string
    {
        $value = ltrim($value, "\xEF\xBB\xBF");

        $value = str_replace(
            ["\xC3\xBF", "\xC3\xA0", "\xC2\xA0", "\xC2\xBF"],
            ' ',
            $value,
        );

        // ÿ (U+00FF), ¿ (U+00BF) — almost never valid in scientific names
        $value = preg_replace('/[\x{00FF}\x{00BF}]/u', ' ', $value);

        // Replace invisible Unicode whitespace (ZWSP, thin space, em space, etc.)
        $value = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{2002}-\x{200A}\x{FEFF}]/u', ' ', $value);

        return preg_replace('/\s{2,}/', ' ', trim($value));
    }

    /**
     * Normalize the scientific name based on various nomenclature rules.
     */
    final protected function normalizeScientificName(Taxon $taxon): void
    {
        $originalName = $taxon->scientificname;
        if (! $originalName) {
            return;
        }

        // Sanitize encoding artifacts before any nomenclature processing
        $name = $this->sanitizeEncodingArtifacts($originalName);

        // Replace non-breaking spaces and other weird whitespace
        $name = preg_replace('/[\xA0\s]+/u', ' ', trim($name));

        $kingdom = strtolower($taxon->kingdom ?? '');
        $isAnimal = ($kingdom === 'animalia');
        $isPlantLike = in_array($kingdom, ['plantae', 'fungi', 'chromista', 'algae'], true);
        $isBacteria = ($kingdom === 'bacteria');

        $name = $this->applyNomenclatureRules($name);
        $name = $this->applyAffinityRules($name);
        $name = $this->applyRankRules($name);
        $name = $this->applyAuthorshipRules($name);
        $name = $this->applyKingdomSpecificRules($name, $isAnimal, $isPlantLike, $isBacteria);

        // Final cleanup of multiple spaces
        $name = preg_replace('/\s+/', ' ', trim($name));

        if ($name !== $originalName) {
            $this->updateTaxaWithOriginalName($taxon, $name, $originalName);
        }
    }

    /**
     * Apply general nomenclature notations and corrections.
     */
    private function applyNomenclatureRules(string $name): string
    {
        // Rule: sensu lato -> s.l.
        $name = preg_replace('/\bsensu lato\b/i', 's.l.', $name);

        // Rule: Remove cf. and cfr.
        $name = preg_replace('/\bcf(r)?\b\.?/i', '', $name);

        // Rule: keep only the part before misidentification notes.
        $name = preg_replace('/^(.+?)\s+mis\s+as\s+.+$/i', '$1', $name);

        // Rule: remove lineage suffixes and keep the base name.
        $name = preg_replace('/^(.+?)\s+lineage\s+[\p{L}\p{N}._-]+(?:\s+.+)?$/ui', '$1', $name);

        // Rule: × (hybrids) -> x (lowercase, with single space)
        $name = preg_replace('/×/u', 'x', $name);
        $name = preg_replace('/\s+x\s+/i', ' x ', $name);
        $name = preg_replace('/^x\s+/i', 'x ', $name);

        return $name;
    }

    /**
     * Resolve affinity notations to base species names.
     */
    private function applyAffinityRules(string $name): string
    {
        // Rule: affinity notation with abbreviated genus should resolve to base species.
        // Example: "Spiroloculina aff. S. communis" -> "Spiroloculina communis"
        $name = preg_replace_callback(
            '/^([A-Z][a-zA-Z-]*)\s+aff\.?\s+([A-Z])\.\s+([a-z][a-zA-Z-]*)(.*)$/u',
            static function (array $matches): string {
                $genus = $matches[1];
                if (strtoupper($genus[0]) !== strtoupper($matches[2])) {
                    return $matches[0];
                }

                return trim("{$genus} {$matches[3]}{$matches[4]}");
            },
            $name,
        );

        // Rule: affinity notation without abbreviated genus should resolve to base species.
        // Example: "Isognomon aff. australicus" -> "Isognomon australicus"
        return preg_replace(
            '/^([A-Z][a-zA-Z-]*)\s+aff\.?\s+([a-z][a-zA-Z-]*)(.*)$/u',
            '$1 $2$3',
            $name,
        );
    }

    /**
     * Normalize rank-related notations and placeholders.
     */
    private function applyRankRules(string $name): string
    {
        // Rule: forma specialis -> f. sp.
        $name = preg_replace('/\bforma specialis\b/i', 'f. sp.', $name);

        // Rule: forma -> f. (avoiding f. sp. which is handled above)
        $name = preg_replace('/\bforma\b(?! sp\.)/i', 'f.', $name);

        // Rule: variant -> var.
        $name = preg_replace('/\bvariant\b/i', 'var.', $name);

        // Rule: slash genus aliases with placeholder ranks should resolve to the first genus.
        $name = preg_replace(
            '/^([A-Z][a-zA-Z-]*)\s*\/\s*[A-Z][a-zA-Z-]*\s+spp?\.?\s*(?:\/\s*spp?\.?)?$/u',
            '$1',
            $name,
        );

        // Rule: genus placeholders (sp/spp) -> genus only
        $name = preg_replace('/^([A-Z][a-zA-Z-]*)\s+spp?\.?$/i', '$1', $name);

        // Rule: keep only the part before "ex" for names such as "Artemia monica ex franciscana"
        $name = preg_replace('/^(.+?)\s+ex\s+.+$/i', '$1', $name);

        // Rule: if "ex" normalization produced genus placeholders, collapse to genus.
        return preg_replace('/^([A-Z][a-zA-Z-]*)\s+spp?\.?$/i', '$1', $name);
    }

    /**
     * Remove trailing authorship and clean special characters.
     */
    private function applyAuthorshipRules(string $name): string
    {
        // Rule: remove trailing authority/year suffixes from canonical names.
        $name = preg_replace(
            '/^([A-Z][a-zA-Z-]*(?:\s+\([A-Z][a-zA-Z-]*\))?(?:\s+[a-z][a-zA-Z-]*){0,2})\s+\([^()]*,\s*\d{4}\)$/u',
            '$1',
            $name,
        );
        $name = preg_replace(
            '/^([A-Z][a-zA-Z-]*\s+\([A-Z][a-zA-Z-]*\)(?:\s+[a-z][a-zA-Z-]*){0,2})\s+[A-Z][\p{L}.\'-]+(?:,\s*|\s+)\d{4}$/u',
            '$1',
            $name,
        );
        $name = preg_replace(
            '/^([A-Z][a-zA-Z-]*\s+\([A-Z][a-zA-Z-]*\)(?:\s+[a-z][a-zA-Z-]*){0,2})\s+[A-Z][\p{L}.\'-]*(?:\s+[a-z]{1,4})?(?:\s+[A-Z][\p{L}.\'-]*)*$/u',
            '$1',
            $name,
        );

        // Rule: replace special characters with spaces
        return preg_replace('/[^\p{L}\p{N}\s.\-()\'"]/u', ' ', $name);
    }

    /**
     * Apply kingdom-specific rules for subgenus and subspecies.
     */
    private function applyKingdomSpecificRules(string $name, bool $isAnimal, bool $isPlantLike, bool $isBacteria): string
    {
        // Rule: pathovar -> pv. (for bacteria/plants)
        if ($isBacteria || $isPlantLike) {
            $name = preg_replace('/\bpathovar\b/i', 'pv.', $name);
        }

        // Rule: cultivar cv. -> '' (single quotes), capitalize cultivar name
        if (preg_match('/\bcv\b\.?\s+[\'\"]?([A-Z][a-z]+)[\'\"]?/i', $name, $matches)) {
            $cultivar = ucfirst(strtolower($matches[1]));
            $name = preg_replace('/\bcv\b\.?\s+[\'\"]?([A-Z][a-z]+)[\'\"]?/i', "'{$cultivar}'", $name);
        }

        // Subgenus Rules
        if ($isPlantLike) {
            $name = preg_replace('/^([A-Z][a-z]+)\s+\(([A-Z][a-z]+)\)(\s+.*)?$/', '$1 subg. $2$3', $name);
        } elseif ($isAnimal) {
            $name = preg_replace('/^([A-Z][a-z]+)\s+subg\.\s+([A-Z][a-z]+)(\s+.*)?$/i', '$1 ($2)$3', $name);
        }

        // Subspecies Rules
        if ($isPlantLike) {
            $name = preg_replace('/\b(ssp|sub|subspecies)\b\.?/i', 'subsp.', $name);
        } elseif ($isAnimal) {
            $name = preg_replace('/\bsubsp\.\s+/i', '', $name);
            $name = preg_replace('/^([A-Z][a-z]+)\s+([a-z]+)\s+\(([a-z]+)\)$/', '$1 $2 $3', $name);
        }

        return $name;
    }

    /**
     * Update the Taxon model with the normalized name and preserve original in notes.
     */
    private function updateTaxaWithOriginalName(Taxon $taxon, string $name, string $originalName): void
    {
        $taxon->scientificname = $name;
        $note = "(original name provided: {$originalName})";

        if (empty($taxon->notes)) {
            $taxon->notes = $note;
        } elseif (! str_contains($taxon->notes, $note)) {
            $taxon->notes .= "\n".$note;
        }
    }

    /**
     * Generate/Normalize LSID based on WoRMS standard.
     */
    final protected function normalizeLsid(Taxon $taxon): void
    {
        if ($taxon->aphia_id) {
            $expectedLsid = "urn:lsid:marinespecies.org:taxname:{$taxon->aphia_id}";

            if (empty($taxon->lsid) || $taxon->lsid !== $expectedLsid) {
                $taxon->lsid = $expectedLsid;
            }
        }
    }
}
