<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Staycation;
use App\Models\StaycationImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public catalogue contract published in docs/api-contract.md.
 *
 * These tests assert the response body a frontend is told to expect, not how
 * the controller assembles it: a refactor that keeps the shape must keep them
 * green, and a change to the shape must be a deliberate contract change.
 */
class ApiStaycationCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_bookable_staycations_in_a_data_envelope(): void
    {
        $staycation = Staycation::factory()->create([
            'house_name' => 'Seaside Retreat',
            'house_price' => 4500,
        ]);

        $response = $this->getJson('/api/v1/staycations');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('data.0.id', $staycation->getKey())
            ->assertJsonPath('data.0.name', 'Seaside Retreat')
            ->assertJsonPath('data.0.price_per_night', '4500.00')
            ->assertJsonPath('data.0.price_per_night_centavos', 450000)
            ->assertJsonPath('data.0.currency', 'PHP')
            ->assertJsonPath('data.0.is_bookable', true)
            ->assertJsonPath('data.0.capacity.maximum_guests', 8)
            ->assertJsonPath('data.0.capacity.included_guests', 6)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_the_listing_hides_staycations_that_are_not_open_for_booking(): void
    {
        $bookable = Staycation::factory()->create();
        Staycation::factory()->unavailable()->create();

        $this->getJson('/api/v1/staycations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $bookable->getKey());
    }

    public function test_the_listing_paginates_and_rejects_an_oversized_page(): void
    {
        Staycation::factory()->count(3)->create();

        $this->getJson('/api/v1/staycations?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/v1/staycations?per_page=500')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_it_returns_a_single_staycation_with_its_gallery(): void
    {
        $staycation = Staycation::factory()->create([
            'house_image' => 'staycations/main.jpg',
        ]);

        StaycationImage::factory()->create([
            'staycation_id' => $staycation->getKey(),
            'image_path' => 'staycations/gallery/one.jpg',
        ]);

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.id', $staycation->getKey())
            ->assertJsonPath('data.image_url', asset('storage/staycations/main.jpg'))
            ->assertJsonPath('data.gallery', [asset('storage/staycations/gallery/one.jpg')]);
    }

    public function test_a_staycation_without_images_still_returns_a_stable_shape(): void
    {
        $staycation = Staycation::factory()->create(['house_image' => null]);

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.image_url', null)
            ->assertJsonPath('data.gallery', [])
            ->assertJsonPath('data.rating.average', null)
            ->assertJsonPath('data.rating.count', 0);
    }

    public function test_the_detail_endpoint_answers_for_a_property_taken_offline(): void
    {
        $staycation = Staycation::factory()->unavailable()->create();

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.availability_status', 'unavailable')
            ->assertJsonPath('data.is_bookable', false);
    }

    public function test_it_summarises_review_ratings_without_exposing_reviewers(): void
    {
        $staycation = Staycation::factory()->create();

        foreach ([4, 5] as $rating) {
            $user = User::factory()->create();
            $booking = Booking::factory()->for($staycation)->for($user)->create();

            Review::factory()->create([
                'user_id' => $user->getKey(),
                'booking_id' => $booking->getKey(),
                'staycation_id' => $staycation->getKey(),
                'rating' => $rating,
            ]);
        }

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.rating.count', 2)
            ->assertJsonPath('data.rating.average', 4.5);
    }

    public function test_it_returns_json_when_a_staycation_does_not_exist(): void
    {
        $this->getJson('/api/v1/staycations/999999')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Staycation not found.']);
    }

    public function test_an_unknown_api_path_returns_json_rather_than_an_html_page(): void
    {
        $response = $this->get('/api/v1/there-is-nothing-here');

        $response->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['message' => 'Resource not found.']);
    }

    /**
     * A caller that omits the Accept header — the default for a browser
     * `fetch()` — must still be answered in JSON, never with a Blade page.
     */
    public function test_errors_stay_json_without_an_accept_header(): void
    {
        $this->get('/api/v1/staycations/999999')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_it_does_not_leak_internal_columns(): void
    {
        $staycation = Staycation::factory()->create();

        $payload = $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->json('data');

        foreach (['house_name', 'house_price', 'house_image', 'created_at', 'updated_at', 'bookings'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $payload);
        }

        $this->assertStringNotContainsString('App\Models', json_encode($payload));
    }
}
