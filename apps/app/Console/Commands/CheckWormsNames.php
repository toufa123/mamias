<?php

namespace App\Console\Commands;

use App\Enums\Catalogue_Status;
use App\Jobs\FetchTaxaFromWormsJob;
use App\Models\Taxon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * The monthly re-check of the catalogue against WoRMS. It queues the same
 * job as the "Fetch from WoRMS" bulk action (progress widget, cancel), which
 * records and scores any new accepted name; the panel banner then points the
 * team to the "Name to update" tab. Manual entries are skipped: WoRMS has
 * nothing for them and would mark them "no data".
 */
#[Signature('taxa:check-worms-names {--sync : Run in this process instead of the queue}')]
#[Description('Re-check every catalogued taxon against WoRMS and flag new accepted names.')]
class CheckWormsNames extends Command
{
    public function handle(): int
    {
        $ids = Taxon::query()
            ->where('catalogue_status', '!=', Catalogue_Status::manual_entry)
            ->pluck('id')
            ->all();

        $job = new FetchTaxaFromWormsJob($ids);

        $this->option('sync') ? dispatch_sync($job) : dispatch($job);

        $this->info(count($ids).' taxa '.($this->option('sync') ? 'checked' : 'queued for checking').' against WoRMS.');

        return self::SUCCESS;
    }
}
