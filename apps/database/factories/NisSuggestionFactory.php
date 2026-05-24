<?php

namespace Database\Factories;

use App\Enums\LiteratureStatus;
use App\Models\NisSuggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NisSuggestion>
 */
class NisSuggestionFactory extends Factory
{
    protected $model = NisSuggestion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'aphia_id' => $this->faker->optional()->numberBetween(100000, 999999),
            'suggested_scientific_name' => $this->faker->unique()->words(2, true),
            'authority' => $this->faker->optional()->lastName(),
            'worms_status' => 'accepted',
            'suggested_common_name' => $this->faker->optional()->word(),
            'depth' => $this->faker->optional()->randomFloat(2, 0, 500),
            'bibliography' => $this->faker->optional()->sentence(),
            'doi' => null,
            'photo_paths' => null,
            'document_paths' => null,
            'status' => LiteratureStatus::PENDING,
            'rejection_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => LiteratureStatus::PENDING]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => LiteratureStatus::APPROVED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => LiteratureStatus::REJECTED,
            'rejection_reason' => 'Not sufficient evidence provided.',
        ]);
    }
}
