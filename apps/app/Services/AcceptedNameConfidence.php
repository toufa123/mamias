<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * How sure we can be that the accepted name WoRMS gives is the same species
 * as the one catalogued as introduced, 0–99 with the reason for each point.
 *
 * The base comes from the kind of change WoRMS reports: a spelling fix or a
 * new genus keeps the same name-bearing type, a subjective synonym is an
 * opinion that two species are one. Adjustments check what should survive
 * the change (original author, epithet, family, rank) and add independent
 * confirmation: the accepted name recorded as alien in the Mediterranean.
 * It checks names, not identifications; a person always decides.
 */
class AcceptedNameConfidence
{
    public const SAFE = 90;

    public const CHECK = 70;

    /** Country codes EASIN uses for Mediterranean states (EL is Greece). */
    private const MEDITERRANEAN = ['AL', 'BA', 'CY', 'DZ', 'EG', 'EL', 'ES', 'FR', 'GR', 'HR', 'IL', 'IT', 'LB', 'LY', 'MA', 'MC', 'ME', 'MT', 'PS', 'SI', 'SY', 'TN', 'TR'];

    public function __construct(
        private readonly WormsService $wormsService,
        private readonly EasinService $easinService,
    ) {}

    /**
     * @param  array<string, mixed>  $record  The WoRMS record of the catalogued name.
     * @param  array<string, mixed>  $accepted  The WoRMS record of its accepted name.
     * @return array{score: int, reasons: array<int, array{label: string, points: int}>}
     */
    public function assess(array $record, array $accepted): array
    {
        $status = (string) ($record['status'] ?? '');
        $spelling = Str::startsWith($status, ['misspelling', 'incorrect grammatical agreement', 'unjustified emendation']);
        $synonym = $status === 'junior subjective synonym';

        $base = match (true) {
            $spelling => 95,
            in_array($status, ['superseded combination', 'junior objective synonym', 'superseded rank'], true) => 85,
            $synonym => 55,
            default => 30,
        };

        $reasons = [['label' => 'WoRMS: '.($status ?: 'no status'), 'points' => $base]];
        $add = function (string $label, int $points) use (&$reasons): void {
            $reasons[] = ['label' => $label, 'points' => $points];
        };

        // A synonym is expected to have its own author and epithet.
        if (! $synonym) {
            $author = self::firstAuthor($record['authority'] ?? null);
            $acceptedAuthor = self::firstAuthor($accepted['authority'] ?? null);

            if ($author && $acceptedAuthor) {
                $author === $acceptedAuthor
                    ? $add('Same original author', 5)
                    : $add('Different original author', -20);
            }

            if (! $spelling && self::epithetStem($record['scientificname'] ?? '') !== self::epithetStem($accepted['scientificname'] ?? '')) {
                $add('Species epithet changed', -15);
            }
        }

        if (($record['family'] ?? null) !== ($accepted['family'] ?? null)) {
            $add('Family changed', -5);
        }

        if (($record['rank'] ?? null) !== ($accepted['rank'] ?? null)) {
            $add('Rank changed', -25);
        }

        if ($where = $this->alienInMediterranean($accepted)) {
            $add("Recorded as alien in the Mediterranean under the new name ({$where})", 10);
        }

        return [
            'score' => max(0, min(99, array_sum(array_column($reasons, 'points')))),
            'reasons' => $reasons,
        ];
    }

    /**
     * @return array{label: string, color: string}
     */
    public static function band(?int $score): array
    {
        return match (true) {
            $score === null => ['label' => 'Not assessed', 'color' => 'gray'],
            $score >= self::SAFE => ['label' => 'Safe to move', 'color' => 'success'],
            $score >= self::CHECK => ['label' => 'Check the source', 'color' => 'warning'],
            default => ['label' => 'Expert review', 'color' => 'danger'],
        };
    }

    /**
     * Where the accepted name is recorded as alien in the Mediterranean,
     * by WoRMS or EASIN, or null.
     *
     * @param  array<string, mixed>  $accepted
     */
    private function alienInMediterranean(array $accepted): ?string
    {
        $worms = collect($this->wormsService->getDistributions((int) $accepted['AphiaID']))
            ->contains(fn (array $record): bool => Str::contains(($record['locality'] ?? '').' '.($record['higherGeography'] ?? ''), 'Mediterranean', ignoreCase: true)
                && in_array(Str::lower($record['establishmentMeans'] ?? ''), ['alien', 'introduced'], true));

        $binomial = trim(preg_replace('/\s*\([^)]*\)\s*/', ' ', (string) $accepted['scientificname']));
        $countries = collect($this->easinService->findSpecies($binomial)['PresentInCountries'] ?? [])
            ->pluck('Country')
            ->intersect(self::MEDITERRANEAN)
            ->sort()
            ->values();

        return match (true) {
            $countries->isNotEmpty() => 'EASIN: '.$countries->implode(', '),
            $worms => 'WoRMS',
            default => null,
        };
    }

    /**
     * Surname of the first (or basionym) author: "(Montagne) Bustamante &
     * Cho, 2021" and "Montagne, 1842" both give "montagne".
     */
    private static function firstAuthor(?string $authority): ?string
    {
        if (blank($authority) || $authority === '[sic]' || ! preg_match('/\(?\s*([^,()&]+)/', $authority, $match)) {
            return null;
        }

        return Str::lower(Str::afterLast(trim($match[1]), ' '));
    }

    /**
     * The species epithet without its Latin gender ending, so a change of
     * genus that only re-agrees the ending (depexum → depexa) still matches.
     */
    private static function epithetStem(string $name): string
    {
        return preg_replace('/(us|um|a|is|e|i)$/', '', Str::lower(Str::afterLast(trim($name), ' ')));
    }
}
