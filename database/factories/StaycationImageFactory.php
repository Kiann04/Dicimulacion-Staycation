<?php

namespace Database\Factories;

use App\Models\Staycation;
use App\Models\StaycationImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaycationImage>
 */
class StaycationImageFactory extends Factory
{
    protected $model = StaycationImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staycation_id' => Staycation::factory(),
            'image_path' => 'staycations/gallery/'.fake()->unique()->slug(2).'.jpg',
        ];
    }
}
