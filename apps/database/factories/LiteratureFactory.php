<?php

namespace Database\Factories;

use App\Enums\LiteratureStatus;
use App\Enums\LiteratureType;
use App\Models\Literature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Literature>
 */
class LiteratureFactory extends Factory
{
    protected $model = Literature::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => null,
            'short_ref' => $this->faker->lastName().' et al., '.$this->faker->year(),
            'full_ref' => $this->faker->sentence(10),
            'type' => $this->faker->randomElement(LiteratureType::cases()),
            'status' => LiteratureStatus::APPROVED,
            'doi' => $this->faker->optional()->numerify('10.####/####'),
            'link' => $this->faker->optional()->url(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LiteratureStatus::PENDING,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LiteratureStatus::REJECTED,
        ]);
    }
}
