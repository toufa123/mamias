<?php

namespace Database\Factories;

use App\Enums\AcforScale;
use App\Enums\Habitat;
use App\Enums\OccurrenceStatus;
use App\Models\IntroEventRecord;
use App\Models\Occurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OccurrenceFactory extends Factory
{
    protected $model = Occurrence::class;

    public function definition(): array
    {
        $lat = $this->faker->latitude(30, 46);
        $lng = $this->faker->longitude(-6, 36);

        return [
            'user_id' => User::factory(),
            'intro_event_record_id' => IntroEventRecord::factory(),
            'location' => [['lat' => $lat, 'lng' => $lng]],
            'depth' => $this->faker->optional()->randomFloat(2, 0, 500),
            'acfor_scale' => $this->faker->optional()->randomElement(AcforScale::cases())?->value,
            'habitats' => $this->faker->optional()->randomElements(Habitat::cases(), 2),
            'photo_paths' => null,
            'notes' => $this->faker->optional()->sentence(),
            'observed_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'status' => OccurrenceStatus::PENDING,
            'moderation_notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => OccurrenceStatus::PENDING]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => OccurrenceStatus::APPROVED]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => OccurrenceStatus::REJECTED,
            'moderation_notes' => 'Location not verified.',
        ]);
    }
}
