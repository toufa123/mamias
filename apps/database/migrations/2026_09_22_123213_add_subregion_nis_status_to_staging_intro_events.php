<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-subregion NIS status, merged from the WMED/CMED/ADRIA/EMED sheets.
 *
 * Those sheets have a "Status of the species" column, and it was tempting to
 * read it as establishment status — it is not. Its values are non-indigenous,
 * cryptogenic, questionable and native: a NIS status, the same vocabulary the
 * PAN sheet uses, recorded per subregion. subregion_records already has a
 * nis_status column waiting for it.
 *
 * Establishment status remains absent from all five sheets and is still set
 * by the reviewer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staging_intro_events', function (Blueprint $table): void {
            foreach (['wmed', 'cmed', 'adria', 'emed'] as $subregion) {
                $table->string("{$subregion}_nis_status")
                    ->nullable()
                    ->after("{$subregion}_establishment_status");
            }
        });
    }

    public function down(): void
    {
        Schema::table('staging_intro_events', function (Blueprint $table): void {
            $table->dropColumn([
                'wmed_nis_status',
                'cmed_nis_status',
                'adria_nis_status',
                'emed_nis_status',
            ]);
        });
    }
};
