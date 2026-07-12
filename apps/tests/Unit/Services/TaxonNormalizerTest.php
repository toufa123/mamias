<?php

declare(strict_types=1);

use App\Enums\Catalogue_Status;
use App\Enums\Environment;
use App\Enums\Worms_Status;
use App\Models\Taxon;
use App\Services\TaxonNormalizer;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->normalizer = new TaxonNormalizer;
});

// normalizeNullableString

it('normalizes null to null string', function () {
    expect($this->normalizer->normalizeNullableString(null))->toBeNull();
});

it('normalizes whitespace-only string to null', function () {
    expect($this->normalizer->normalizeNullableString('   '))->toBeNull();
});

it('trims surrounding whitespace from a non-empty string', function () {
    expect($this->normalizer->normalizeNullableString('  hello  '))->toBe('hello');
});

it('normalizes a BackedEnum to its string value', function () {
    expect($this->normalizer->normalizeNullableString(Catalogue_Status::checked_accepted))->toBe('checked & accepted');
    expect($this->normalizer->normalizeNullableString(Worms_Status::accepted))->toBe('accepted');
});

// normalizeNullableInt

it('normalizes null to null int', function () {
    expect($this->normalizer->normalizeNullableInt(null))->toBeNull();
});

it('normalizes empty string to null int', function () {
    expect($this->normalizer->normalizeNullableInt(''))->toBeNull();
});

it('casts a numeric string to int', function () {
    expect($this->normalizer->normalizeNullableInt('42'))->toBe(42);
});

it('returns an integer value unchanged', function () {
    expect($this->normalizer->normalizeNullableInt(7))->toBe(7);
});

// normalizeEnvironments

it('converts an Environment enum array to sorted value strings', function () {
    $result = $this->normalizer->normalizeEnvironments([Environment::marine, Environment::freshwater]);
    expect($result)->toBe(['freshwater', 'marine']);
});

it('decodes a JSON environment string', function () {
    $result = $this->normalizer->normalizeEnvironments('["marine","freshwater"]');
    expect($result)->toBe(['freshwater', 'marine']);
});

it('returns empty array for a non-array non-string input', function () {
    expect($this->normalizer->normalizeEnvironments(42))->toBe([]);
});

it('preserves valid environments and filters null values', function () {
    $result = $this->normalizer->normalizeEnvironments([null, Environment::marine]);
    expect($result)->toBe(['marine']);
});

it('handles a plain string environment as a single-element array', function () {
    $result = $this->normalizer->normalizeEnvironments('marine');
    expect($result)->toBe(['marine']);
});

it('handles environment label strings via fromLabelOrValue', function () {
    $result = $this->normalizer->normalizeEnvironments(['Marine', 'Freshwater']);
    expect($result)->toBe(['freshwater', 'marine']);
});

it('passes unrecognised environment strings through normaliseNullableString', function () {
    $result = $this->normalizer->normalizeEnvironments(['marine', '  unknown-env  ']);
    expect($result)->toBe(['marine', 'unknown-env']);
});

// normalizeSynonyms

it('normalizes a synonym array with expected keys', function () {
    $synonyms = [[
        'AphiaID' => '123',
        'scientificname' => ' Foo bar ',
        'authority' => null,
        'status' => 'accepted',
        'unacceptreason' => null,
    ]];

    $result = $this->normalizer->normalizeSynonyms($synonyms);

    expect($result)->toHaveCount(1)
        ->and($result[0]['AphiaID'])->toBe(123)
        ->and($result[0]['scientificname'])->toBe('Foo bar')
        ->and($result[0]['authority'])->toBeNull()
        ->and($result[0]['status'])->toBe('accepted');
});

it('returns an empty array for invalid synonym JSON', function () {
    expect($this->normalizer->normalizeSynonyms('not-valid-json'))->toBe([]);
});

it('filters out non-array synonym entries', function () {
    $synonyms = [
        'string-entry',
        ['AphiaID' => 1, 'scientificname' => 'A', 'authority' => null, 'status' => null, 'unacceptreason' => null],
    ];
    expect($this->normalizer->normalizeSynonyms($synonyms))->toHaveCount(1);
});

it('sorts synonyms by AphiaID ascending', function () {
    $synonyms = [
        ['AphiaID' => 200, 'scientificname' => 'Beta', 'authority' => null, 'status' => null, 'unacceptreason' => null],
        ['AphiaID' => 100, 'scientificname' => 'Alpha', 'authority' => null, 'status' => null, 'unacceptreason' => null],
    ];
    $result = $this->normalizer->normalizeSynonyms($synonyms);
    expect($result[0]['AphiaID'])->toBe(100)
        ->and($result[1]['AphiaID'])->toBe(200);
});

it('parses synonym JSON and normalises each entry', function () {
    $json = json_encode([
        ['AphiaID' => '42', 'scientificname' => ' Foo ', 'authority' => 'Bar', 'status' => null, 'unacceptreason' => null],
    ]);
    $result = $this->normalizer->normalizeSynonyms($json);
    expect($result[0]['AphiaID'])->toBe(42)
        ->and($result[0]['scientificname'])->toBe('Foo');
});

it('returns empty array for a non-array non-string synonym input', function () {
    expect($this->normalizer->normalizeSynonyms(42))->toBe([]);
});

it('handles missing optional keys in synonym entries', function () {
    $synonyms = [
        ['AphiaID' => 1],
    ];
    $result = $this->normalizer->normalizeSynonyms($synonyms);
    expect($result[0]['AphiaID'])->toBe(1)
        ->and($result[0]['scientificname'])->toBeNull()
        ->and($result[0]['authority'])->toBeNull()
        ->and($result[0]['status'])->toBeNull()
        ->and($result[0]['unacceptreason'])->toBeNull();
});

// sanitizeEncodingArtifacts

it('replaces ÿ with space in scientific names', function () {
    $result = $this->normalizer->sanitizeEncodingArtifacts('Caulerpaÿcylindracea');
    expect($result)->toBe('Caulerpa cylindracea');
});

it('strips UTF-8 BOM from start of string', function () {
    $result = $this->normalizer->sanitizeEncodingArtifacts("\xEF\xBB\xBFCaulerpa");
    expect($result)->toBe('Caulerpa');
});

it('replaces non-breaking spaces with regular spaces', function () {
    $result = $this->normalizer->sanitizeEncodingArtifacts("Caulerpa\xC2\xA0cylindracea");
    expect($result)->toBe('Caulerpa cylindracea');
});

it('collapses multiple spaces from artifact replacement', function () {
    $result = $this->normalizer->sanitizeEncodingArtifacts('Caulerpa ÿ  cylindracea');
    expect($result)->toBe('Caulerpa cylindracea');
});

it('handles clean strings without modification', function () {
    $result = $this->normalizer->sanitizeEncodingArtifacts('Caulerpa cylindracea');
    expect($result)->toBe('Caulerpa cylindracea');
});

it('replaces ¿ (inverted question mark) with space', function () {
    $result = $this->normalizer->sanitizeEncodingArtifacts('Caulerpa¿cylindracea');
    expect($result)->toBe('Caulerpa cylindracea');
});

it('replaces zero-width spaces and thin spaces', function () {
    $result = $this->normalizer->sanitizeEncodingArtifacts("Caulerpa\xE2\x80\x8Bcylindracea");
    expect($result)->toBe('Caulerpa cylindracea');
});

// normalize — scientific name rules (end-to-end via normalize)

it('normalizes affinity with abbreviated genus', function () {
    $taxon = new Taxon(['scientificname' => 'Spiroloculina aff. S. communis']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Spiroloculina communis');
});

it('normalizes affinity with full genus', function () {
    $taxon = new Taxon(['scientificname' => 'Isognomon aff. australicus']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Isognomon australicus');
});

it('expands sensu lato to s.l.', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia sensu lato']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia s.l.');
});

it('removes cf. notation', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia cf. viridis']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia viridis');
});

it('removes cfr. notation', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia cfr. viridis']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia viridis');
});

it('removes mis as suffix', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia mis as something else']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia');
});

it('removes lineage suffix', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia lineage something']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia');
});

it('converts hybrid × to x', function () {
    $taxon = new Taxon(['scientificname' => 'Hibiscus × rosasinensis']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Hibiscus x rosasinensis');
});

it('converts forma specialis to f. sp.', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia forma specialis']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia f. sp.');
});

it('converts forma to f.', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia forma']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia f.');
});

it('does not double-convert forma specialis', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia forma specialis']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->not->toContain('f. sp. specialis');
});

it('converts variant to var.', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia variant']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia var.');
});

it('removes sp. placeholder keeping genus', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia sp.']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia');
});

it('removes spp. placeholder keeping genus', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia spp.']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia');
});

it('removes trailing authorship in parentheses', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia viridis (Montagu, 1804)']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia viridis');
});

it('removes content after ex notation', function () {
    $taxon = new Taxon(['scientificname' => 'Artemia monica ex franciscana']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Artemia monica');
});

it('converts plant subgenus parentheses to subg. notation', function () {
    $taxon = new Taxon(['scientificname' => 'Solanum (Solanum)', 'kingdom' => 'Plantae']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Solanum subg. Solanum');
});

it('converts animal subg. notation to parentheses', function () {
    $taxon = new Taxon(['scientificname' => 'Mytilus subg. Mytilus', 'kingdom' => 'Animalia']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Mytilus (Mytilus)');
});

it('converts plant ssp to subsp.', function () {
    $taxon = new Taxon(['scientificname' => 'Solanum ssp tuberosum', 'kingdom' => 'Plantae']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Solanum subsp. tuberosum');
});

it('converts plant sub to subsp.', function () {
    $taxon = new Taxon(['scientificname' => 'Solanum sub tuberosum', 'kingdom' => 'Plantae']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Solanum subsp. tuberosum');
});

it('removes subsp. notation for animals', function () {
    $taxon = new Taxon(['scientificname' => 'Mytilus subsp. edulis', 'kingdom' => 'Animalia']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Mytilus edulis');
});

it('leaves already-normalised names unchanged', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia viridis']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia viridis');
});

it('preserves original name in notes when name changes', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia cf. viridis']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia viridis');
    expect($taxon->notes)->toContain('(original name provided: Elysia cf. viridis)');
});

it('appends original name note when existing notes present', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia cf. viridis', 'notes' => 'Previous note']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->notes)->toContain('Previous note')
        ->and($taxon->notes)->toContain('(original name provided: Elysia cf. viridis)');
});

it('handles empty scientificname without error', function () {
    $taxon = new Taxon(['scientificname' => '']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('');
});

it('handles null scientificname without error', function () {
    $taxon = new Taxon(['scientificname' => null]);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBeNull();
});

it('converts pathovar to pv. for bacteria kingdom', function () {
    $taxon = new Taxon(['scientificname' => 'Pseudomonas pathovar tomato', 'kingdom' => 'Bacteria']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Pseudomonas pv. tomato');
});

it('converts pathovar to pv. for plant kingdom', function () {
    $taxon = new Taxon(['scientificname' => 'Xanthomonas pathovar oryzae', 'kingdom' => 'Plantae']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Xanthomonas pv. oryzae');
});

it('handles cultivar notation', function () {
    $taxon = new Taxon(['scientificname' => 'Rosa cv. Peace', 'kingdom' => 'Plantae']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe("Rosa 'Peace'");
});

it('removes slash genus aliases with rank placeholders', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia / Calliphylla sp.']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia');
});

it('keeps genus only for spp. after ex cleanup', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia sp. ex something']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Elysia');
});

it('processes kingdom-specific rules for fungi (plant-like)', function () {
    $taxon = new Taxon(['scientificname' => 'Amanita (Amanita)', 'kingdom' => 'Fungi']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Amanita subg. Amanita');
});

it('processes kingdom-specific rules for chromista (plant-like)', function () {
    $taxon = new Taxon(['scientificname' => 'Phaeocystis ssp globosa', 'kingdom' => 'Chromista']);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe('Phaeocystis subsp. globosa');
});

it('does not change scientificname when already clean', function () {
    $taxon = new Taxon(['scientificname' => 'Elysia viridis']);
    $before = $taxon->scientificname;
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->scientificname)->toBe($before);
});

// normalizeLsid (tested through normalize)

it('generates LSID from aphia_id when lsid is empty', function () {
    $taxon = new Taxon(['aphia_id' => 123, 'lsid' => null]);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->lsid)->toBe('urn:lsid:marinespecies.org:taxname:123');
});

it('does not change a correct LSID', function () {
    $taxon = new Taxon([
        'aphia_id' => 123,
        'lsid' => 'urn:lsid:marinespecies.org:taxname:123',
    ]);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->lsid)->toBe('urn:lsid:marinespecies.org:taxname:123');
});

it('overwrites a wrong LSID', function () {
    $taxon = new Taxon([
        'aphia_id' => 456,
        'lsid' => 'urn:lsid:marinespecies.org:taxname:123',
    ]);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->lsid)->toBe('urn:lsid:marinespecies.org:taxname:456');
});

it('does not generate LSID when aphia_id is null', function () {
    $taxon = new Taxon(['aphia_id' => null, 'lsid' => null]);
    (new TaxonNormalizer)->normalize($taxon);
    expect($taxon->lsid)->toBeNull();
});
