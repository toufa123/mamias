<?php

use App\Filament\Forms\Components\CountrySelectWithMedPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Store every introduction event's first_country under its MAMIAS name.
 *
 * The baseline import wrote names ("Israel"), while events edited in the
 * admin form got ISO codes ("IL"); a few carry typos ("Irael", "Turkey"). The
 * form now reads and writes names, and rejected every value it did not
 * recognise ("The selected 1st Country of Introduction is invalid").
 *
 * A value naming two countries around a note — "Israel backdated by Türkiye" —
 * keeps both countries, and the note moves to the event's notes. Anything
 * still unrecognised is left as it is. Data only; nothing to reverse.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('intro_event_records')
            ->whereNotNull('first_country')
            ->orderBy('id')
            ->select(['id', 'first_country', 'notes'])
            ->chunkById(500, function ($events): void {
                foreach ($events as $event) {
                    $countries = json_decode((string) $event->first_country, true);

                    if (! is_array($countries)) {
                        continue;
                    }

                    $names = [];
                    $moved = [];

                    foreach ($countries as $value) {
                        $value = (string) $value;
                        $name = CountrySelectWithMedPriority::canonicalName($value);

                        if ($name !== null) {
                            $names[] = $name;

                            continue;
                        }

                        $parts = preg_split('/\s+backdat\w*\s+(?:by|to)\s+/iu', $value);
                        $split = array_map(fn (string $part): ?string => CountrySelectWithMedPriority::canonicalName($part), $parts);

                        if (count($parts) === 2 && ! in_array(null, $split, true)) {
                            array_push($names, ...$split);
                            $moved[] = $value;
                        } else {
                            $names[] = $value;
                        }
                    }

                    $names = array_values(array_unique($names));

                    if ($names === $countries && $moved === []) {
                        continue;
                    }

                    $notes = trim(implode("\n", array_filter([
                        $event->notes,
                        ...array_map(fn (string $value): string => "(1st country of introduction provided: \"{$value}\")", $moved),
                    ])));

                    DB::table('intro_event_records')->where('id', $event->id)->update([
                        'first_country' => json_encode($names, JSON_UNESCAPED_UNICODE),
                        'notes' => $notes === '' ? null : $notes,
                    ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
