<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IntroEventPathwayReconciler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Reconciles the EASIN "Pathway check" on introduction events. Dry run by
 * default: prints the decisions and saves them to a CSV. --apply settles the
 * agreements; the proposals that change MAMIAS pathways need --with.
 */
#[Signature('intro-events:review-pathways
    {--apply : Settle the agreements (every decision that keeps MAMIAS unchanged)}
    {--with=* : Also apply these proposals: add-corridor, adopt-easin}')]
#[Description('Reconcile introduction-event pathways with EASIN: agreements cleared, proposals and conflicts listed.')]
class ReviewIntroEventPathwaysCommand extends Command
{
    public function handle(IntroEventPathwayReconciler $reconciler): int
    {
        $decisions = $reconciler->decisions();

        if ($decisions->isEmpty()) {
            $this->info('No introduction event has a pathway check left.');

            return self::SUCCESS;
        }

        $path = 'review/intro-event-pathways-'.now()->format('Ymd-His').'.csv';
        $rows = $decisions->map(fn (array $d): array => [$d['event'], $d['species'], $d['easin_id'], implode(' ', $d['mamias']), implode(' ', $d['easin']), $d['decision'], $d['detail']]);
        Storage::put($path, collect([['event', 'species', 'EASIN id', 'MAMIAS categories', 'EASIN primary categories', 'decision', 'detail'], ...$rows])
            ->map(fn (array $row): string => implode(',', array_map(fn (mixed $cell): string => '"'.str_replace('"', '""', (string) $cell).'"', $row)))
            ->implode("\n")."\n");

        $counts = $decisions->countBy('decision');
        $this->table(['decision', 'events'], collect(IntroEventPathwayReconciler::DECISIONS)->map(fn (string $d): array => [$d, $counts[$d] ?? 0])->all());
        $this->line("{$decisions->count()} events. Full list: ".Storage::path($path));

        if (! $this->option('apply')) {
            $this->comment('Dry run, nothing written. Re-run with --apply (and --with=add-corridor,adopt-easin to apply those proposals).');

            return self::SUCCESS;
        }

        $chosen = [...IntroEventPathwayReconciler::AGREEMENTS, ...array_intersect((array) $this->option('with'), IntroEventPathwayReconciler::PROPOSALS)];
        $settled = $reconciler->apply($chosen);
        $this->info('Settled: '.collect($settled)->map(fn (int $n, string $d): string => "{$d} {$n}")->implode(', ').'.');

        return self::SUCCESS;
    }
}
