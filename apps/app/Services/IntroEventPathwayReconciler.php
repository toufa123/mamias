<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\PathwayType;
use App\Models\IntroEventRecord;
use Illuminate\Support\Collection;

/**
 * Reconciles the "Pathway check" (pathway_check) the EASIN comparison left on
 * introduction events. MAMIAS is the validated Mediterranean inventory while
 * EASIN's pathways are EU-wide (Atlantic, Black Sea and inland waters too), so
 * EASIN confirms or suggests and never overwrites. Rules, first match wins:
 *
 *   no-easin           the EASIN entry cannot be read                      → left as it is
 *   easin-no-primary   EASIN lists only secondary (spread) pathways        → agreement, check cleared
 *   adopt-easin        MAMIAS has no pathway                                → proposal: EASIN's primary pathway
 *   same-categories    same CBD categories, only subcategories differ     → agreement, check cleared
 *   mamias-more        EASIN's categories all in MAMIAS (MAMIAS adds the
 *                      Mediterranean-specific ones: unaided spread, Suez) → agreement, check cleared
 *   parasite-host      MAMIAS 4.2 (parasite on a host), EASIN adds
 *                      Corridor: the host came through Suez, both hold    → agreement, check cleared
 *   add-corridor       EASIN adds Corridor and the first record is
 *                      Levantine (Lessepsian signal)                        → proposal: add Corridor 5.1
 *   easin-more         EASIN adds other categories (EU-wide contexts)     → cleared, extras kept as a note
 *   corridor-confirmed no overlap, MAMIAS Corridor with a Levantine first
 *                      record: EASIN's pathway reflects non-Med records   → agreement, check cleared
 *   same-vector        no overlap, both sides only Release/Escape (the
 *                      aquarium trade, where the two are undecidable)     → agreement, check cleared
 *   conflict           anything else with no category in common           → left for an expert
 *
 * Only EASIN's primary pathways (Path_type "P", sent in either case) are
 * compared; its secondary ones describe onward spread, not introduction.
 */
final class IntroEventPathwayReconciler
{
    public const DECISIONS = ['same-categories', 'mamias-more', 'parasite-host', 'add-corridor', 'easin-more', 'corridor-confirmed', 'same-vector', 'easin-no-primary', 'adopt-easin', 'conflict', 'no-easin'];

    /** Decisions applied by --apply: the check is settled, MAMIAS unchanged. */
    public const AGREEMENTS = ['same-categories', 'mamias-more', 'parasite-host', 'easin-more', 'corridor-confirmed', 'same-vector', 'easin-no-primary'];

    /** Decisions that change MAMIAS pathways; applied only when asked for. */
    public const PROPOSALS = ['add-corridor', 'adopt-easin'];

    /** First-record countries that point to a Lessepsian (Suez) arrival. */
    private const LEVANTINE = ['Israel', 'Lebanon', 'Syria', 'Egypt', 'Türkiye', 'Cyprus', 'Gaza strip'];

    /** EASIN category names, stripped to letters => CBD category. */
    private const EASIN_CATEGORIES = [
        'RELEASEINNATURE' => CbdPathwayCategory::ReleaseIntoNature,
        'ESCAPEFROMCONFINEMENT' => CbdPathwayCategory::EscapeFromConfinement,
        'TRANSPORTSTOWAWAY' => CbdPathwayCategory::TransportStowaway,
        'TRANSPORTCONTAMINANT' => CbdPathwayCategory::TransportContaminant,
        'CORRIDOR' => CbdPathwayCategory::Corridor,
        'UNAIDED' => CbdPathwayCategory::Unaided,
    ];

    /** EASIN subcategories with an exact MAMIAS equivalent, stripped to letters. */
    private const EASIN_SUBCATEGORIES = [
        'INTERCONNECTEDWATERWAYSBASINSSEAS' => CbdPathwaySubcategory::Corridor_5_1,
    ];

    public function __construct(private EasinService $easin) {}

    /**
     * One decision per event that still carries a pathway check.
     *
     * @return Collection<int, array{event: int, species: string, easin_id: ?string, mamias: list<string>, easin: list<string>, decision: string, detail: string}>
     */
    public function decisions(): Collection
    {
        return IntroEventRecord::query()
            ->whereNotNull('pathway_check')
            ->with(['taxon:id,scientificname', 'pathwayRecords'])
            ->orderBy('id')
            ->get()
            ->map(fn (IntroEventRecord $event): array => $this->decide($event));
    }

    /**
     * @return array{event: int, species: string, easin_id: ?string, mamias: list<string>, easin: list<string>, decision: string, detail: string}
     */
    public function decide(IntroEventRecord $event): array
    {
        preg_match('/vs EASIN (R\d+)/', (string) $event->pathway_check, $match);
        $easinId = $match[1] ?? null;
        $entry = $easinId === null ? null : $this->easin->findById($easinId);

        $mamias = $event->pathwayRecords->pluck('category')->map(fn (CbdPathwayCategory $category): string => $category->value)->unique()->sort()->values()->all();
        $primaries = collect($entry['CBD_Pathways'] ?? [])->filter(self::isPrimary(...));
        $easin = $primaries->map(fn (array $pathway): ?string => self::easinCategory((string) ($pathway['Name'] ?? ''))?->value)->filter()->unique()->sort()->values()->all();

        $base = ['event' => $event->id, 'species' => (string) $event->taxon?->getAttribute('scientificname'), 'easin_id' => $easinId, 'mamias' => $mamias, 'easin' => $easin];
        $extra = array_values(array_diff($easin, $mamias));
        $levantine = array_intersect((array) $event->first_country, self::LEVANTINE) !== [];
        $corridor = CbdPathwayCategory::Corridor->value;
        $parasite = $event->pathwayRecords->pluck('subcategory')->contains(CbdPathwaySubcategory::TransportContaminant_4_2);
        $commodity = [CbdPathwayCategory::ReleaseIntoNature->value, CbdPathwayCategory::EscapeFromConfinement->value];
        $labels = fn (array $codes): string => implode(', ', array_map(fn (string $code): string => (string) CbdPathwayCategory::from($code)->getLabel(), $codes));

        return $base + match (true) {
            $entry === null => ['decision' => 'no-easin', 'detail' => 'EASIN entry could not be read'],
            $easin === [] && $mamias !== [] => ['decision' => 'easin-no-primary', 'detail' => 'EASIN lists no primary (introduction) pathway; MAMIAS kept'],
            $easin === [] => ['decision' => 'no-easin', 'detail' => 'neither MAMIAS nor EASIN has an introduction pathway'],
            $mamias === [] => ['decision' => 'adopt-easin', 'detail' => 'MAMIAS has no pathway; EASIN primary: '.$primaries->pluck('Name')->implode('; ')],
            $mamias === $easin => ['decision' => 'same-categories', 'detail' => 'same CBD categories; only subcategories differ'],
            $extra === [] => ['decision' => 'mamias-more', 'detail' => 'EASIN categories all in MAMIAS; MAMIAS adds '.$labels(array_values(array_diff($mamias, $easin)))],
            $parasite && in_array($corridor, $extra, true) => ['decision' => 'parasite-host', 'detail' => 'MAMIAS 4.2 parasite on host; EASIN Corridor is how the host arrived'],
            $levantine && in_array($corridor, $extra, true) => ['decision' => 'add-corridor', 'detail' => 'EASIN adds Corridor and the first record is Levantine ('.implode(', ', (array) $event->first_country).')'],
            array_intersect($mamias, $easin) !== [] => ['decision' => 'easin-more', 'detail' => 'EASIN also lists (EU-wide) '.$labels($extra)],
            $levantine && in_array($corridor, $mamias, true) => ['decision' => 'corridor-confirmed', 'detail' => 'MAMIAS Corridor with a Levantine first record; EASIN (EU-wide) says '.$labels($easin)],
            array_diff([...$mamias, ...$easin], $commodity) === [] => ['decision' => 'same-vector', 'detail' => 'release vs escape (same trade vector): MAMIAS '.$labels($mamias).' / EASIN '.$labels($easin)],
            default => ['decision' => 'conflict', 'detail' => 'no category in common: MAMIAS '.$labels($mamias).' / EASIN '.$labels($easin)],
        };
    }

    /**
     * Settle the checks whose decision is in $decisions: agreements are
     * cleared, add-corridor adds Corridor 5.1, adopt-easin adds EASIN's primary
     * pathway when it has an exact MAMIAS subcategory. Each settled event gets
     * a dated note saying what EASIN said and what was done.
     *
     * @param  list<string>  $decisions
     * @return array<string, int> decision => events settled
     */
    public function apply(array $decisions): array
    {
        $settled = array_fill_keys($decisions, 0);

        foreach ($this->decisions() as $decision) {
            if (in_array($decision['decision'], $decisions, true) && $this->settle(IntroEventRecord::query()->findOrFail($decision['event']), $decision)) {
                $settled[$decision['decision']]++;
            }
        }

        return $settled;
    }

    /**
     * Settle one event's check: add-corridor and adopt-easin add their
     * pathway first, then a dated note is written and the check cleared.
     * False when adopt-easin has no exact MAMIAS subcategory to add.
     *
     * @param  array{easin_id: ?string, decision: string, detail: string}  $decision
     */
    public function settle(IntroEventRecord $event, array $decision): bool
    {
        if ($decision['decision'] === 'add-corridor') {
            $event->pathwayRecords()->create(['category' => CbdPathwayCategory::Corridor, 'subcategory' => CbdPathwaySubcategory::Corridor_5_1, 'pathway_type' => PathwayType::Primary, 'description' => 'Added from EASIN '.$decision['easin_id'].' (Lessepsian first record)']);
        }

        if ($decision['decision'] === 'adopt-easin') {
            $adopted = $this->adoptable($decision['easin_id']);

            if ($adopted === null) {
                return false;
            }

            $event->pathwayRecords()->create([...$adopted, 'pathway_type' => PathwayType::Primary, 'description' => 'Adopted from EASIN '.$decision['easin_id']]);
        }

        $event->notes = trim($event->notes."\nPathway check (EASIN {$decision['easin_id']}) reconciled ".now()->toDateString().": {$decision['decision']} — {$decision['detail']}");
        // Kept so the event moves to the Pathway checked tab with its outcome.
        $event->pathway_resolution = ['decision' => $decision['decision'], 'detail' => $decision['detail'], 'easin_id' => $decision['easin_id'], 'check' => $event->pathway_check];
        $event->pathway_checked_at = now();
        $event->pathway_check = null;
        $event->save();

        return true;
    }

    /**
     * EASIN's first primary pathway as a MAMIAS category and subcategory, or
     * null when its subcategory has no exact MAMIAS equivalent.
     *
     * @return array{category: CbdPathwayCategory, subcategory: CbdPathwaySubcategory}|null
     */
    private function adoptable(?string $easinId): ?array
    {
        $pathway = collect($easinId === null ? [] : ($this->easin->findById($easinId)['CBD_Pathways'] ?? []))
            ->first(self::isPrimary(...));
        [$category, $subcategory] = array_pad(explode(':', (string) ($pathway['Name'] ?? ''), 2), 2, '');
        $mapped = self::EASIN_SUBCATEGORIES[self::letters($subcategory)] ?? null;

        return $mapped === null || self::easinCategory($category) === null
            ? null
            : ['category' => self::easinCategory($category), 'subcategory' => $mapped];
    }

    /** @param  array<string, mixed>  $pathway */
    private static function isPrimary(array $pathway): bool
    {
        return strtoupper((string) ($pathway['Path_type'] ?? 'P')) === 'P';
    }

    private static function easinCategory(string $name): ?CbdPathwayCategory
    {
        return self::EASIN_CATEGORIES[self::letters(explode(':', $name, 2)[0])] ?? null;
    }

    private static function letters(string $value): string
    {
        return (string) preg_replace('/[^A-Z]/', '', strtoupper($value));
    }
}
