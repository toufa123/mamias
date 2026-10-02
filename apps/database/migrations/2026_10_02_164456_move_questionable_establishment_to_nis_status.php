<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Questionable" is no longer an establishment status: in Galanidi et al.
 * 2023 a questionable record is a NIS status (insufficient information or
 * uncertain identification). Wherever an establishment status said so, the
 * NIS status beside it becomes Questionable, unless it already holds a
 * held-out status, and the establishment is cleared as not assessed.
 *
 * Country records carry no NIS status; theirs is only cleared.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pairs = [
            'intro_event_records' => [['establishment_status', 'nis_status']],
            'subregion_records' => [['establishment_status', 'nis_status']],
            'country_records' => [['establishment_status', null]],
            'staging_intro_events' => [
                ['establishment_status', 'nis_status'],
                ['wmed_establishment_status', 'wmed_nis_status'],
                ['cmed_establishment_status', 'cmed_nis_status'],
                ['adria_establishment_status', 'adria_nis_status'],
                ['emed_establishment_status', 'emed_nis_status'],
            ],
        ];

        foreach ($pairs as $table => $columns) {
            foreach ($columns as [$establishment, $nis]) {
                if ($nis !== null) {
                    DB::table($table)
                        ->where($establishment, 'Questionable')
                        ->where(fn ($query) => $query->whereNull($nis)->orWhere($nis, 'NIS'))
                        ->update([$nis => 'Questionable']);
                }

                DB::table($table)->where($establishment, 'Questionable')->update([$establishment => null]);
            }
        }
    }

    /**
     * Not reversible: which establishment values were "Questionable" is not
     * kept, and the NIS status now says the same thing.
     */
    public function down(): void {}
};
