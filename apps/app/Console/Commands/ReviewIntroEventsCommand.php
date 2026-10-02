<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IntroEventReviewResolver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Proposes values for the introduction events flagged needs_review and, only
 * with --apply, writes them. Dry run by default: prints a summary and saves
 * every proposal, with its rule and the EASIN comparison, to a CSV for review.
 */
#[Signature('intro-events:review
    {--apply : Write the proposals of the chosen categories (default: the safe ones) that have no conflict}
    {--with=* : Also apply these opt-in categories: before, decade, historical}')]
#[Description('Propose (and optionally apply) values for introduction events that need review, checked against EASIN.')]
class ReviewIntroEventsCommand extends Command
{
    public function handle(IntroEventReviewResolver $resolver): int
    {
        $proposals = $resolver->proposals();

        if ($proposals->isEmpty()) {
            $this->info('No introduction event needs review.');

            return self::SUCCESS;
        }

        $path = 'review/intro-event-review-'.now()->format('Ymd-His').'.csv';
        $rows = $proposals->map(fn (array $p): array => [$p['event'], $p['species'], $p['field'], $p['raw'], $p['proposed'] ?? '', $p['category'], $p['rule'], $p['easin'], $p['conflict'] ? 'yes' : '']);
        Storage::put($path, collect([['event', 'species', 'field', 'raw value', 'proposed', 'category', 'rule', 'EASIN check', 'conflict'], ...$rows])
            ->map(fn (array $row): string => implode(',', array_map(fn (mixed $cell): string => '"'.str_replace('"', '""', (string) $cell).'"', $row)))
            ->implode("\n")."\n");

        $this->table(
            ['category', 'cells', 'with a proposal', 'conflicts'],
            $proposals->groupBy('category')->map(fn ($group, string $category): array => [
                $category,
                $group->count(),
                $group->whereNotNull('proposed')->count(),
                $group->where('conflict', true)->count(),
            ])->sortKeys()->values()->all(),
        );
        $this->line(sprintf('%d cells on %d events. Full list: %s', $proposals->count(), $proposals->pluck('event')->unique()->count(), Storage::path($path)));

        if (! $this->option('apply')) {
            $this->comment('Dry run, nothing written. Re-run with --apply (and --with=before,decade,historical to include those).');

            return self::SUCCESS;
        }

        $categories = [...IntroEventReviewResolver::SAFE_CATEGORIES, ...array_intersect((array) $this->option('with'), ['before', 'decade', 'historical'])];
        $result = $resolver->apply($categories);
        $this->info("Applied {$result['applied']} values ({$this->categoriesLabel($categories)}); {$result['events_cleared']} events no longer need review.");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $categories
     */
    private function categoriesLabel(array $categories): string
    {
        return implode(', ', $categories);
    }
}
