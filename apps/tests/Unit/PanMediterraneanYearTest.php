<?php

declare(strict_types=1);

use App\Filament\Imports\PanMediterraneanImporter;

/**
 * The Mediterranean first-introduction year is the earliest subregion record:
 * a species entered the sea when it was first seen anywhere in it.
 */
it('takes the earliest subregion year when the sheet gives none', function (): void {
    expect(PanMediterraneanImporter::earliestMediterraneanYear(null, ['CMED' => 2018, 'EMED' => 2019]))
        ->toBe(['year' => 2018, 'from' => 'CMED']);
});

it('corrects a stated year that is later than a subregion record', function (): void {
    // The sheet claims 2005, but EMED has it in 1997 — 1997 is when it
    // arrived in the Mediterranean.
    expect(PanMediterraneanImporter::earliestMediterraneanYear(2005, ['WMED' => 2005, 'EMED' => 1997]))
        ->toBe(['year' => 1997, 'from' => 'EMED']);
});

it('leaves a stated year alone when it is already the earliest', function (): void {
    // The Med-wide column may predate every subregion sheet's coverage; that
    // is still the first record.
    expect(PanMediterraneanImporter::earliestMediterraneanYear(1950, ['WMED' => 2005, 'EMED' => 1997]))
        ->toBeNull();
});

it('leaves a stated year alone when it ties with the earliest', function (): void {
    expect(PanMediterraneanImporter::earliestMediterraneanYear(2018, ['CMED' => 2018, 'EMED' => 2019]))
        ->toBeNull();
});

it('does nothing when no subregion has a year', function (): void {
    expect(PanMediterraneanImporter::earliestMediterraneanYear(2018, []))->toBeNull()
        ->and(PanMediterraneanImporter::earliestMediterraneanYear(null, []))->toBeNull();
});
