<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\LiteratureStatus;
use App\Enums\OccurrenceStatus;
use App\Models\IntroEventRecord;
use App\Models\NisSuggestion;
use App\Models\Occurrence;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Demo data for the guide screenshots (resources/docs/*.md, resources/docs/screenshots/):
 * occurrence reports of well-known Mediterranean NIS (pending, resubmitted, approved,
 * rejected) from three demo contributors, and NIS suggestions from the first one.
 *
 * --contributor=email hands that first contributor's records to an existing
 * account, so the public "My …" pages can be shot logged in as it. Every record
 * created is listed in storage/app/guide-demo.json, and --remove deletes exactly
 * those plus the demo contributors (emails @guide-demo.mamias.local): real data,
 * the borrowed account included, is never touched.
 */
#[Signature('guides:demo-data
    {--contributor= : Email of an existing account that receives the first demo contributor\'s records}
    {--remove : Delete everything a previous run created}')]
#[Description('Seed (or remove) demo occurrences and NIS suggestions for the guide screenshots.')]
class GuidesDemoDataCommand extends Command
{
    private const DOMAIN = 'guide-demo.mamias.local';

    private const LEDGER = 'guide-demo.json';

    /**
     * The first contributor's suggestions, newest first.
     *
     * @var list<array<string, mixed>>
     */
    private const SUGGESTIONS = [
        ['name' => 'Penaeus aztecus', 'authority' => 'Ives, 1891', 'aphia' => 377748, 'days' => 2, 'status' => 'pending', 'at' => [36.85, 10.35], 'depth' => 30],
        ['name' => 'Portunus segnis', 'authority' => '(Forskål, 1775)', 'aphia' => 1063551, 'days' => 18, 'status' => 'approved', 'at' => [34.75, 10.85], 'depth' => 5],
        ['name' => 'Carcinus aestuarii', 'authority' => 'Nardo, 1847', 'aphia' => 107380, 'days' => 30, 'status' => 'rejected', 'at' => [37.05, 10.30], 'depth' => 1,
            'reason' => 'Carcinus aestuarii is native to the Mediterranean, so it is not a non-indigenous species here.'],
    ];

    private const CONTRIBUTORS = [
        'leila' => ['Leila', 'Haddad', 'TN'],
        'marco' => ['Marco', 'Rossi', 'IT'],
        'eleni' => ['Eleni', 'Papadaki', 'GR'],
    ];

    /**
     * Newest submission first, which is the order the list shows them in.
     *
     * @var list<array<string, mixed>>
     */
    private const OCCURRENCES = [
        ['by' => 'leila', 'species' => 'Fistularia commersonii', 'at' => [34.32, 10.72], 'days' => 1, 'status' => 'pending',
            'depth' => 14, 'acfor' => 'frequent', 'habitats' => ['sand'], 'extent' => [3, 'individuals', 'measured'],
            'notes' => 'Three individuals swimming together over the sand, seen on a survey dive.',
            'moderation' => 'The point was on the shore. Please move it to where you were diving.'],
        ['by' => 'marco', 'species' => 'Callinectes sapidus', 'at' => [40.68, 0.92], 'days' => 2, 'status' => 'pending',
            'depth' => 2, 'acfor' => 'abundant', 'habitats' => ['sand'], 'extent' => [120, 'individuals', 'estimated'],
            'notes' => 'Caught in trammel nets by local fishers, many every night this week.'],
        ['by' => 'eleni', 'species' => 'Rhopilema nomadica', 'at' => [36.32, 28.30], 'days' => 3, 'status' => 'pending',
            'depth' => 0, 'acfor' => 'abundant', 'habitats' => ['unknown'], 'extent' => null,
            'notes' => 'Large swarm along the beach, several bathers stung.'],
        ['by' => 'leila', 'species' => 'Siganus luridus', 'at' => [35.49, 12.60], 'days' => 4, 'status' => 'pending',
            'depth' => 6, 'acfor' => 'common', 'habitats' => ['rocks'], 'extent' => null, 'notes' => null],
        ['by' => 'eleni', 'species' => 'Pterois miles', 'at' => [35.02, 34.08], 'days' => 9, 'status' => 'approved',
            'depth' => 18, 'acfor' => 'frequent', 'habitats' => ['rocks'], 'extent' => [4, 'individuals', 'measured'],
            'notes' => 'Four lionfish under the same overhang.', 'moderation' => 'Clear photos, thank you.'],
        ['by' => 'marco', 'species' => 'Caulerpa cylindracea', 'at' => [43.20, 5.36], 'days' => 12, 'status' => 'approved',
            'depth' => 12, 'acfor' => 'common', 'habitats' => ['rocks', 'sand'], 'extent' => [35, 'm2', 'estimated'], 'notes' => null],
        ['by' => 'eleni', 'species' => 'Halophila stipulacea', 'at' => [36.19, 29.62], 'days' => 15, 'status' => 'approved',
            'depth' => 8, 'acfor' => 'common', 'habitats' => ['seagrass_meadows'], 'extent' => [60, 'm2', 'measured'],
            'notes' => 'Patch at the edge of a Posidonia meadow, measured with a tape.'],
        ['by' => 'leila', 'species' => 'Lagocephalus sceleratus', 'at' => [33.90, 11.20], 'days' => 20, 'status' => 'approved',
            'depth' => 25, 'acfor' => 'rare', 'habitats' => ['sand'], 'extent' => [1, 'individuals', 'measured'],
            'notes' => 'Landed by a trawler; kept for the national fisheries institute.'],
        ['by' => 'marco', 'species' => 'Mnemiopsis leidyi', 'at' => [45.30, 12.48], 'days' => 6, 'status' => 'rejected',
            'depth' => 1, 'acfor' => 'abundant', 'habitats' => ['unknown'], 'extent' => null, 'notes' => 'Comb jellies in the harbour.',
            'moderation' => 'The description fits Beroe ovata, a native comb jelly. Please add a photo showing the lobes if you still think it is Mnemiopsis.'],
        ['by' => 'leila', 'species' => 'Siganus rivulatus', 'at' => [35.82, 14.47], 'days' => 25, 'status' => 'rejected',
            'depth' => 5, 'acfor' => 'occasional', 'habitats' => ['rocks'], 'extent' => null, 'notes' => null,
            'moderation' => 'Duplicate of an occurrence already reported from this dive.'],
    ];

    public function handle(): int
    {
        $this->remove();

        if ($this->option('remove')) {
            $this->info('Demo data removed.');

            return self::SUCCESS;
        }

        $borrowed = $this->option('contributor') ? User::where('email', $this->option('contributor'))->first() : null;

        if ($this->option('contributor') && $borrowed === null) {
            $this->error("No account with the email {$this->option('contributor')}.");

            return self::FAILURE;
        }

        $created = ['occurrences' => [], 'suggestions' => []];

        $contributors = collect(self::CONTRIBUTORS)->map(function (array $person, string $key) use ($borrowed): User {
            if ($borrowed && $key === array_key_first(self::CONTRIBUTORS)) {
                return $borrowed;
            }

            [$first, $last, $country] = $person;
            $user = User::create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => "{$key}@".self::DOMAIN,
                'password' => Str::random(40),
                'country' => $country,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user->assignRole('user');
        });

        foreach (self::OCCURRENCES as $demo) {
            $event = IntroEventRecord::whereHas('taxon', fn ($query) => $query->where('scientificname', $demo['species']))->first();

            if ($event === null) {
                $this->warn("Skipped {$demo['species']}: it has no introduction event here.");

                continue;
            }

            [$lat, $lng] = $demo['at'];
            [$extent, $unit, $method] = $demo['extent'] ?? [null, null, null];
            $submitted = now()->subDays($demo['days'])->setTime(9 + $demo['days'] % 8, 15 * ($demo['days'] % 4));

            $occurrence = Occurrence::create([
                'user_id' => $contributors[$demo['by']]->id,
                'intro_event_record_id' => $event->id,
                'location' => [['lat' => $lat, 'lng' => $lng]],
                'depth' => $demo['depth'],
                'acfor_scale' => $demo['acfor'],
                'coverage_value' => $extent,
                'coverage_unit' => $unit,
                'coverage_method' => $method,
                'habitats' => $demo['habitats'],
                'notes' => $demo['notes'],
                'observed_at' => $submitted->copy()->subDays(2)->setTime(10, 30),
                'status' => OccurrenceStatus::from($demo['status']),
                'moderation_notes' => $demo['moderation'] ?? null,
            ]);
            $occurrence->forceFill(['created_at' => $submitted, 'updated_at' => $submitted])->saveQuietly();
            $created['occurrences'][] = $occurrence->id;
        }

        foreach (self::SUGGESTIONS as $demo) {
            [$lat, $lng] = $demo['at'];
            $submitted = now()->subDays($demo['days'])->setTime(11, 0);

            $suggestion = NisSuggestion::create([
                'user_id' => $contributors[array_key_first(self::CONTRIBUTORS)]->id,
                'suggested_scientific_name' => $demo['name'],
                'authority' => $demo['authority'],
                'aphia_id' => $demo['aphia'],
                'worms_status' => 'accepted',
                'location' => [['lat' => $lat, 'lng' => $lng]],
                'depth' => $demo['depth'],
                'status' => LiteratureStatus::from($demo['status']),
                'rejection_reason' => $demo['reason'] ?? null,
            ]);
            $suggestion->forceFill(['created_at' => $submitted, 'updated_at' => $submitted])->saveQuietly();
            $created['suggestions'][] = $suggestion->id;
        }

        Storage::put(self::LEDGER, json_encode($created));

        $this->info(sprintf('Seeded %d demo occurrences and %d suggestions%s. Remove them with: php artisan guides:demo-data --remove',
            count($created['occurrences']), count($created['suggestions']), $borrowed ? " (the first contributor's for {$borrowed->email})" : ''));

        return self::SUCCESS;
    }

    private function remove(): void
    {
        $users = User::where('email', 'like', '%@'.self::DOMAIN)->get();
        $ledger = json_decode((string) Storage::get(self::LEDGER), true) ?: [];

        $occurrences = Occurrence::whereIn('user_id', $users->pluck('id'))->pluck('id')->merge($ledger['occurrences'] ?? []);
        $suggestions = NisSuggestion::withTrashed()->whereIn('user_id', $users->pluck('id'))->pluck('id')->merge($ledger['suggestions'] ?? []);

        Activity::where('subject_type', (new Occurrence)->getMorphClass())->whereIn('subject_id', $occurrences)->delete();
        Activity::where('subject_type', (new NisSuggestion)->getMorphClass())->whereIn('subject_id', $suggestions)->delete();
        Occurrence::whereIn('id', $occurrences)->delete();
        NisSuggestion::withTrashed()->whereIn('id', $suggestions)->forceDelete();
        $users->each->delete();
        Storage::delete(self::LEDGER);
    }
}
