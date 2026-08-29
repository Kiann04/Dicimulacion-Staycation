<?php

namespace Database\Factories;

use App\Models\BlockedDate;
use App\Models\Staycation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlockedDate>
 */
class BlockedDateFactory extends Factory
{
    protected $model = BlockedDate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(5, 40))->startOfDay();

        return [
            'staycation_id' => Staycation::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(2)->toDateString(),
            'reason' => fake()->randomElement(['Maintenance', 'Owner stay', 'Deep cleaning']),
        ];
    }

    public function forDates(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}
