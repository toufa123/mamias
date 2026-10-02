<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstablishmentStatus;
use App\Models\CountryRecord;
use App\Models\IntroEventRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Create CountryRecord model instances with intro event, country, establishment status and first-record year. */
/** @extends Factory<CountryRecord> */
class CountryRecordFactory extends Factory
{
    protected $model = CountryRecord::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'intro_event_id' => IntroEventRecord::factory(),
            'country' => fake()->randomElement(['Italy', 'Greece', 'Egypt', 'Spain', 'France', 'Tunisia', 'Israel', 'Cyprus', 'Malta']),
            'establishment_status' => fake()->randomElement(EstablishmentStatus::cases()),
            'first_record_year' => fake()->year(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
