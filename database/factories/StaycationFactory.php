<?php

namespace Database\Factories;

use App\Models\Staycation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staycation>
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
            'house_name' => fake()->streetName().' Villa',
            'house_description' => fake()->paragraph(),
            'house_price' => fake()->randomElement([2500, 3000, 4500, 5000]),
            'house_image' => null,
            'house_location' => fake()->city(),
            'house_availability' => 'available',
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'house_availability' => 'unavailable',
        ]);
    }

    public function pricedAt(float|int|string $pricePerNight): static
    {
        return $this->state(fn (array $attributes) => [
            'house_price' => $pricePerNight,
        ]);
    }
}
