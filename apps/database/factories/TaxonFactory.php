<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Catalogue_Status;
use App\Enums\Worms_Status;
use App\Models\Taxon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Taxon>
 */
class TaxonFactory extends Factory
{
    protected $model = Taxon::class;

    public function definition(): array
    {
        return [
            'scientificname' => fake()->unique()->words(2, true),
            'authority' => fake()->name(),
            'aphia_id' => fake()->numberBetween(1, 999999),
            'worms_status' => Worms_Status::accepted,
            'catalogue_status' => Catalogue_Status::not_checked,
            'rank' => fake()->randomElement(['Species', 'Genus', 'Family']),
            'kingdom' => 'Animalia',
            'phylum' => fake()->word(),
            'class' => fake()->word(),
            'order' => fake()->word(),
            'family' => fake()->word(),
            'genus' => fake()->word(),
            'is_extinct' => false,
            'environments' => ['marine'],
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'worms_status' => Worms_Status::accepted,
            'catalogue_status' => Catalogue_Status::checked_accepted,
        ]);
    }

    public function unaccepted(): static
    {
        return $this->state(fn () => [
            'worms_status' => Worms_Status::unaccepted,
            'catalogue_status' => Catalogue_Status::checked_not_accepted,
            'unacceptreason' => 'synonym',
        ]);
    }
}
