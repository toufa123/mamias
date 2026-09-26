<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\LiteratureType;
use App\Services\DoiMetadataService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns null when DOI fetch is unsuccessful', function () {
    Http::fake([
        'api.crossref.org/*' => Http::response(null, 404),
    ]);

    $service = new DoiMetadataService;
    $result = $service->fetchFromCrossref('10.1234/invalid');

    expect($result)->toBeNull();
});

it('formats metadata correctly from Crossref response', function () {
    Http::fake([
        'api.crossref.org/*' => Http::response([
            'message' => [
                'author' => [
                    ['family' => 'Smith', 'given' => 'John'],
                    ['family' => 'Doe', 'given' => 'Jane'],
                ],
                'title' => ['Fabulous Study on Marine Biology'],
                'published-print' => ['date-parts' => [[2024]]],
                'type' => 'journal-article',
                'container-title' => ['Ocean Science'],
                'volume' => '15',
                'issue' => '2',
                'page' => '100-110',
                'URL' => 'https://doi.org/10.1234/test',
            ],
        ], 200),
    ]);

    $service = new DoiMetadataService;
    $result = $service->fetchFromCrossref('10.1234/test');

    expect($result)->toBeArray()
        ->and($result['short_ref'])->toBe('Smith et al., 2024')
        ->and($result['full_ref'])->toBe('Smith, John; Doe, Jane (2024). Fabulous Study on Marine Biology. Ocean Science, 15(2), 100-110.')
        ->and($result['type'])->toBe(LiteratureType::ARTICLE)
        ->and($result['link'])->toBe('https://doi.org/10.1234/test');
});

it('handles missing author and title gracefully', function () {
    Http::fake([
        'api.crossref.org/*' => Http::response([
            'message' => [
                'type' => 'report',
                'published-online' => ['date-parts' => [[2023]]],
            ],
        ], 200),
    ]);

    $service = new DoiMetadataService;
    $result = $service->fetchFromCrossref('10.1234/no-data');

    expect($result)->toBeArray()
        ->and($result['short_ref'])->toBe('Unknown, 2023')
        ->and($result['full_ref'])->toBe('Unknown Authors (2023). No Title.')
        ->and($result['type'])->toBe(LiteratureType::TECHNICAL_REPORT);
});

it('handles missing publication date', function () {
    Http::fake([
        'api.crossref.org/*' => Http::response([
            'message' => [
                'author' => [['family' => 'Lonely']],
                'title' => ['Empty Year'],
                'type' => 'book',
            ],
        ], 200),
    ]);

    $service = new DoiMetadataService;
    $result = $service->fetchFromCrossref('10.1234/no-year');

    expect($result['short_ref'])->toBe('Lonely, n.d.')
        ->and($result['full_ref'])->toContain('(n.d.)');
});

it('normalizes pasted DOI forms to the bare lowercase DOI', function (?string $input, ?string $expected) {
    expect(DoiMetadataService::normalize($input))->toBe($expected);
})->with([
    ['10.1234/ABC', '10.1234/abc'],
    ['https://doi.org/10.1234/abc', '10.1234/abc'],
    ['http://dx.doi.org/10.1234/abc', '10.1234/abc'],
    ['  doi: 10.1234/abc ', '10.1234/abc'],
    ['', null],
    [null, null],
]);

it('falls back to the issued date and flags retractions', function () {
    Http::fake([
        'api.crossref.org/*' => Http::response([
            'message' => [
                'author' => [['family' => 'Solo']],
                'title' => ['Retracted Study'],
                'issued' => ['date-parts' => [[2019, 5]]],
                'type' => 'journal-article',
                'updated-by' => [['type' => 'retraction', 'DOI' => '10.1234/notice']],
            ],
        ], 200),
    ]);

    $result = (new DoiMetadataService)->fetchFromCrossref('https://doi.org/10.1234/RETRACTED');

    expect($result['year'])->toBe(2019)
        ->and($result['short_ref'])->toBe('Solo, 2019')
        ->and($result['is_retracted'])->toBeTrue();

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/works/10.1234%2Fretracted'));
});

it('suggests a DOI only when the found title appears in the reference', function () {
    Http::fake([
        'api.crossref.org/works?*' => Http::sequence()
            ->push(['message' => ['items' => [['DOI' => '10.5555/MATCH', 'title' => ['Lessepsian migration of fishes into the Mediterranean']]]]])
            ->push(['message' => ['items' => [['DOI' => '10.5555/other', 'title' => ['An unrelated paper about coral reef ecology']]]]])
            ->push(['message' => ['items' => [['DOI' => '10.5555/possessive', 'title' => ['Lessepsian migration of fishes into the Mediterranean’s basin']]]]]),
    ]);

    $service = new DoiMetadataService;
    $reference = 'Golani, D. (1998). Lessepsian Migration of Fishes into the Mediterranean basin. Bull. Yale 103.';

    expect($service->searchDoi($reference))->toBe('10.5555/match')
        ->and($service->searchDoi($reference))->toBeNull()
        ->and($service->searchDoi($reference))->toBe('10.5555/possessive');
});
