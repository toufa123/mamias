<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EstablishmentStatus;
use App\Enums\NisStatus;
use App\Models\IntroEventRecord;
use App\Models\Literature;
use App\Models\Taxon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntroEventRecord>
 */
class IntroEventRecordFactory extends Factory
{
    protected $model = IntroEventRecord::class;

    public function definition(): array
    {
        return [
            'taxon_id' => Taxon::factory(),
            'first_introduction_year' => fake()->year(),
            'first_country' => fake()->country(),
            'nis_status' => NisStatus::NIS,
            'establishment_status' => EstablishmentStatus::Established,
            'literature_id' => Literature::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
