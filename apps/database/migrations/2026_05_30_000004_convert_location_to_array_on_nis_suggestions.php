<?php

use App\Models\NisSuggestion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Convert single-object JSON location to array format on nis_suggestions table. */
return new class extends Migration
{
    public function up(): void
    {
        NisSuggestion::query()
            ->whereNotNull('location')
            ->eachById(function (NisSuggestion $suggestion): void {
                $raw = $suggestion->getRawOriginal('location');
                if ($raw === null) {
                    return;
                }

                $decoded = json_decode($raw, true);

                if (isset($decoded['lat'], $decoded['lng'])) {
                    DB::table('nis_suggestions')
                        ->where('id', $suggestion->id)
                        ->update(['location' => json_encode([$decoded])]);
                }
            });
    }

    public function down(): void
    {
        NisSuggestion::query()
            ->whereNotNull('location')
            ->eachById(function (NisSuggestion $suggestion): void {
                $raw = $suggestion->getRawOriginal('location');
                if ($raw === null) {
                    return;
                }

                $decoded = json_decode($raw, true);

                if (is_array($decoded) && isset($decoded[0]['lat'], $decoded[0]['lng'])) {
                    DB::table('nis_suggestions')
                        ->where('id', $suggestion->id)
                        ->update(['location' => json_encode($decoded[0])]);
                }
            });
    }
};
