<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NisStatus;
use App\Enums\Subregion;
use App\Models\IntroEventRecord;
use App\Models\SubregionRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubregionRecord>
 */
class SubregionRecordFactory extends Factory
{
    protected $model = SubregionRecord::class;

    public function definition(): array
    {
        return [
            'intro_event_id' => IntroEventRecord::factory(),
            'subregion' => fake()->randomElement(Subregion::cases()),
            'nis_status' => fake()->randomElement(NisStatus::cases()),
            'first_arrival_year' => fake()->year(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
