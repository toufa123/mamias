<?php

declare(strict_types=1);

use App\Services\SubregionHeaderDisambiguator;

beforeEach(function (): void {
    $this->disambiguator = new SubregionHeaderDisambiguator;
});

it('renames each paired subregion header by position', function (): void {
    // The real header row partners send: status first, year second.
    $headers = ['', 'Species', 'author_id', 'YEAR OF FIRST INTRODUCTION IN Med',
        'WMED', 'WMED', 'CMED', 'CMED', 'ADRIA', 'ADRIA', 'EMED', 'EMED', 'status'];

    expect($this->disambiguator->disambiguate($headers))->toBe([
        '', 'Species', 'author_id', 'YEAR OF FIRST INTRODUCTION IN Med',
        'wmed_establishment_status', 'wmed_first_arrival_year',
        'cmed_establishment_status', 'cmed_first_arrival_year',
        'adria_establishment_status', 'adria_first_arrival_year',
        'emed_establishment_status', 'emed_first_arrival_year',
        'status',
    ]);
});

it('leaves a lone subregion column alone', function (): void {
    // One column carries no positional information — guessing which of the two
    // roles it fills would invent data, so it stays for manual mapping.
    expect($this->disambiguator->disambiguate(['Species', 'WMED', 'CMED', 'CMED']))
        ->toBe(['Species', 'WMED', 'cmed_establishment_status', 'cmed_first_arrival_year']);
});

it('matches headers regardless of case and surrounding space', function (): void {
    expect($this->disambiguator->disambiguate([' wmed ', 'WMed']))
        ->toBe(['wmed_establishment_status', 'wmed_first_arrival_year']);
});

it('ignores columns that are not subregions', function (): void {
    expect($this->disambiguator->disambiguate(['status', 'status', 'citation']))
        ->toBe(['status', 'status', 'citation']);
});

it('rewrites only the header row of a stream', function (): void {
    $csv = "Species,WMED,WMED\nAblennes hians,cas,2018\n\"Quoted, name\",est,2019\n";

    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $csv);
    rewind($stream);

    $result = $this->disambiguator->rewriteStream($stream);
    $out = stream_get_contents($result);
    fclose($result);

    expect($out)->toBe(
        "Species,wmed_establishment_status,wmed_first_arrival_year\n"
        ."Ablennes hians,cas,2018\n"
        ."\"Quoted, name\",est,2019\n"
    );
});

it('preserves a semicolon delimiter', function (): void {
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, "Species;WMED;WMED\nAblennes hians;cas;2018\n");
    rewind($stream);

    $result = $this->disambiguator->rewriteStream($stream);
    $out = stream_get_contents($result);
    fclose($result);

    expect($out)->toBe(
        "Species;wmed_establishment_status;wmed_first_arrival_year\n"
        ."Ablennes hians;cas;2018\n"
    );
});
