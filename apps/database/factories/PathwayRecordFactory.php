<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\PathwayType;
use App\Models\IntroEventRecord;
use App\Models\PathwayRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PathwayRecord>
 */
class PathwayRecordFactory extends Factory
{
    protected $model = PathwayRecord::class;

    public function definition(): array
    {
        $category = fake()->randomElement(CbdPathwayCategory::cases());
        $subcategory = fake()->randomElement(CbdPathwaySubcategory::cases());

        return [
            'intro_event_id' => IntroEventRecord::factory(),
            'category' => $category,
            'subcategory' => $subcategory,
            'pathway_type' => fake()->randomElement(PathwayType::cases()),
            'description' => fake()->optional()->sentence(),
            'uncertainty' => fake()->randomElement(DataQuality::cases()),
        ];
    }
}
