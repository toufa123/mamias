<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DoiMetadataService;
use App\Enums\LiteratureType;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns null when DOI fetch is unsuccessful', function () {
    Http::fake([
        'api.crossref.org/*' => Http::response(null, 404),
    ]);

    $service = new DoiMetadataService();
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

    $service = new DoiMetadataService();
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

    $service = new DoiMetadataService();
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

    $service = new DoiMetadataService();
    $result = $service->fetchFromCrossref('10.1234/no-year');

    expect($result['short_ref'])->toBe('Lonely, n.d.')
        ->and($result['full_ref'])->toContain('(n.d.)');
});
