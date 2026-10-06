<?php

use App\Models\IntroEventRecord;
use App\Models\NisSuggestion;
use App\Models\Occurrence;
use App\Models\Taxon;
use App\Models\User;

beforeEach(function () {
    IntroEventRecord::factory()->for(Taxon::factory()->state(['scientificname' => 'Pterois miles']))->create();
    IntroEventRecord::factory()->for(Taxon::factory()->state(['scientificname' => 'Siganus luridus']))->create();
});

it('seeds demo occurrences and suggestions and removes every trace of them', function () {
    $real = Occurrence::factory()->create();

    $this->artisan('guides:demo-data')->assertSuccessful();
    $this->artisan('guides:demo-data')->assertSuccessful(); // re-running replaces, never duplicates

    expect(Occurrence::count())->toBe(3)
        ->and(NisSuggestion::count())->toBe(3)
        ->and(User::where('email', 'like', '%@guide-demo.mamias.local')->count())->toBe(3);

    $this->artisan('guides:demo-data --remove')->assertSuccessful();

    expect(Occurrence::pluck('id')->all())->toBe([$real->id])
        ->and(NisSuggestion::withTrashed()->count())->toBe(0)
        ->and(User::where('email', 'like', '%@guide-demo.mamias.local')->exists())->toBeFalse();
});

it('hands the first contributor\'s records to an existing account and leaves that account in place', function () {
    $account = User::factory()->create(['email' => 'contributor@example.test']);
    $own = Occurrence::factory()->for($account)->create();

    $this->artisan('guides:demo-data --contributor=contributor@example.test')->assertSuccessful();

    // Siganus luridus is one of the first contributor's reports; Pterois miles is not.
    expect(Occurrence::where('user_id', $account->id)->count())->toBe(2)
        ->and(NisSuggestion::where('user_id', $account->id)->count())->toBe(3)
        ->and(User::where('email', 'like', '%@guide-demo.mamias.local')->count())->toBe(2);

    $this->artisan('guides:demo-data --remove')->assertSuccessful();

    expect($account->fresh())->not->toBeNull()
        ->and(Occurrence::where('user_id', $account->id)->pluck('id')->all())->toBe([$own->id])
        ->and(NisSuggestion::withTrashed()->count())->toBe(0);
});

it('refuses an unknown contributor email', function () {
    $this->artisan('guides:demo-data --contributor=nobody@example.test')->assertFailed();

    expect(Occurrence::count())->toBe(0);
});
