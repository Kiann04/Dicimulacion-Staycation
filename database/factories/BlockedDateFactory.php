<?php

namespace Database\Factories;

use App\Models\BlockedDate;
use App\Models\Staycation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedDate>
 */
class BlockedDateFactory extends Factory
{
    protected $model = BlockedDate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staycation_id' => Staycation::factory(),
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->addDays(2)->toDateString(),
            'reason' => 'Maintenance',
        ];
    }
}
