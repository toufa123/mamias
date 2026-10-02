<?php

declare(strict_types=1);

use App\Filament\Imports\IntroEventRecordImporter;
use App\Filament\Resources\IntroEventRecords\Pages\CreateIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\EditIntroEventRecord;
use App\Filament\Resources\IntroEventRecords\Pages\ListIntroEventRecords;
use App\Models\IntroEventRecord;
use App\Models\Taxon;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('views an event as read-only facts, with the review callout only when flagged', function () {
    $flagged = IntroEventRecord::factory()->create([
        'first_introduction_year' => 2018,
        'first_country' => ['Syria'],
        'notes' => IntroEventRecordImporter::REVIEW_NOTE_PREFIX.'Year: 18xx; Country: Syrie',
    ]);
    $flagged->needs_review = true;
    $flagged->save();
    $clean = IntroEventRecord::factory()->create();

    livewire(ListIntroEventRecords::class)
        ->loadTable()
        ->mountAction(TestAction::make('view')->table($flagged))
        ->assertSchemaComponentExists('review_callout', checkComponentUsing: fn ($component): bool => $component->isVisible()
            && str_contains($component->toHtml(), 'Year: 18xx · Country: Syrie'))
        ->assertSchemaStateSet(['first_introduction_year' => 2018]);

    livewire(ListIntroEventRecords::class)
        ->loadTable()
        ->mountAction(TestAction::make('view')->table($clean))
        ->assertSchemaComponentExists('review_callout', checkComponentUsing: fn ($component): bool => $component->isHidden());
});

it('labels the species without doubled brackets, the authority hidden where the field is narrow but kept in the tooltip', function () {
    $taxon = Taxon::factory()->create(['scientificname' => 'Ablennes hians', 'authority' => '(Valenciennes, 1846)']);
    $event = IntroEventRecord::factory()->for($taxon)->create();

    livewire(ListIntroEventRecords::class)
        ->loadTable()
        ->mountAction(TestAction::make('edit')->table($event))
        ->assertFormFieldExists('taxon_id', checkFieldUsing: function ($field) use ($taxon): bool {
            $label = $field->getOptionLabelFromRecord($taxon);

            return $label === '<span title="Ablennes hians (Valenciennes, 1846)"><i>Ablennes hians</i><span class="lg:hidden"> (Valenciennes, 1846)</span></span>';
        });
});

it('offers a new introduction event only the species that have none yet, and an edited event its own species too', function () {
    $recorded = Taxon::factory()->create(['scientificname' => 'Recorded species']);
    $event = IntroEventRecord::factory()->for($recorded)->create();
    $unrecorded = Taxon::factory()->create(['scientificname' => 'Unrecorded species']);

    $optionKeys = fn ($field): array => array_map('intval', array_keys($field->getOptions()));

    livewire(CreateIntroEventRecord::class)
        ->assertFormFieldExists('taxon_id', checkFieldUsing: fn ($field): bool => in_array($unrecorded->id, $optionKeys($field), true)
            && ! in_array($recorded->id, $optionKeys($field), true));

    livewire(EditIntroEventRecord::class, ['record' => $event->getRouteKey()])
        ->assertFormFieldExists('taxon_id', checkFieldUsing: fn ($field): bool => in_array($recorded->id, $optionKeys($field), true)
            && in_array($unrecorded->id, $optionKeys($field), true));
});

it('counts the species left for a new event beside the species field, only when creating', function () {
    Taxon::factory()->count(2)->create();
    $event = IntroEventRecord::factory()->create();

    livewire(CreateIntroEventRecord::class)
        ->assertSeeHtml('Species without an introduction event')
        ->assertSeeHtml('>'.Taxon::whereDoesntHave('introEvents')->count().'<');

    livewire(EditIntroEventRecord::class, ['record' => $event->getRouteKey()])
        ->assertDontSeeHtml('Species without an introduction event');
});
