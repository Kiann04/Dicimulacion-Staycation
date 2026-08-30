<?php

namespace Database\Factories;

use App\Models\Staycation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staycation>
 */
class StaycationFactory extends Factory
{
    protected $model = Staycation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'house_name' => fake()->unique()->streetName().' Staycation',
            'house_description' => fake()->paragraph(),
            'house_price' => fake()->numberBetween(1000, 8000),
            'house_image' => 'staycations/example.jpg',
            'house_location' => fake()->city(),
            'house_availability' => 'available',
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'house_availability' => 'unavailable',
        ]);
    }
}
