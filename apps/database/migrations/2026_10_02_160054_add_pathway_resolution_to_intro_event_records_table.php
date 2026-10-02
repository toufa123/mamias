<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keep the outcome of a settled pathway check, so settled events stay listed
 * (Pathway checked tab) instead of vanishing once pathway_check is cleared.
 *
 * Events settled before this column existed are backfilled from the dated
 * note the reconciler wrote; their original EASIN check text was not kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table): void {
            $table->timestamp('pathway_checked_at')->nullable()->index();
            $table->json('pathway_resolution')->nullable();
        });

        DB::table('intro_event_records')
            ->where('notes', 'like', '%Pathway check (EASIN%reconciled%')
            ->select(['id', 'notes'])
            ->orderBy('id')
            ->each(function (object $event): void {
                preg_match_all('/Pathway check \(EASIN (\S*)\) reconciled (\d{4}-\d{2}-\d{2}): (\S+) — (.*)$/mu', (string) $event->notes, $matches, PREG_SET_ORDER);

                if ($matches === []) {
                    return;
                }

                [, $easinId, $date, $decision, $detail] = end($matches);

                DB::table('intro_event_records')->where('id', $event->id)->update([
                    'pathway_checked_at' => $date,
                    'pathway_resolution' => json_encode(['decision' => $decision, 'detail' => $detail, 'easin_id' => $easinId, 'check' => null], JSON_UNESCAPED_UNICODE),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('intro_event_records', function (Blueprint $table): void {
            $table->dropColumn(['pathway_checked_at', 'pathway_resolution']);
        });
    }
};
