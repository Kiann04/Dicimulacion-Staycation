<?php

namespace Tests\Feature\Api\V1;

use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The client is on another domain and cannot resolve a relative path or guess
 * Laravel's storage layout, so the API has to hand it URLs that work as-is.
 * Payment proofs are the deliberate exception and must never appear in a body.
 */
class MediaUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_staycation_image_is_an_absolute_url_under_the_storage_path(): void
    {
        URL::forceRootUrl('http://api.example.com');

        $staycation = Staycation::factory()->create(['house_image' => 'staycations/1700000000.jpg']);

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.image_url', 'http://api.example.com/storage/staycations/1700000000.jpg');
    }

    /** Older rows were written with the prefix already applied; they must not gain a second one. */
    public function test_a_path_that_already_carries_the_storage_prefix_is_not_prefixed_twice(): void
    {
        URL::forceRootUrl('http://api.example.com');

        $staycation = Staycation::factory()->create(['house_image' => 'storage/staycations/old.jpg']);

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.image_url', 'http://api.example.com/storage/staycations/old.jpg');
    }

    public function test_a_full_url_is_passed_through_unchanged(): void
    {
        $staycation = Staycation::factory()->create(['house_image' => 'https://cdn.example.com/a.jpg']);

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.image_url', 'https://cdn.example.com/a.jpg');
    }

    public function test_a_missing_image_is_null_rather_than_a_broken_url(): void
    {
        $staycation = Staycation::factory()->create(['house_image' => null]);

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.image_url', null);
    }

    public function test_gallery_images_are_absolute_urls_too(): void
    {
        URL::forceRootUrl('http://api.example.com');

        $staycation = Staycation::factory()->create();
        $staycation->images()->create(['image_path' => 'staycations/gallery/g1.jpg']);

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.images.0.url', 'http://api.example.com/storage/staycations/gallery/g1.jpg');
    }

    public function test_the_catalogue_listing_returns_absolute_image_urls(): void
    {
        URL::forceRootUrl('http://api.example.com');

        Staycation::factory()->create(['house_image' => 'staycations/list.jpg']);

        $this->getJson('/api/v1/staycations')
            ->assertOk()
            ->assertJsonPath('data.0.image_url', 'http://api.example.com/storage/staycations/list.jpg');
    }

    /**
     * A proof is personal financial information. It is streamed to the owner by
     * PaymentProofController and its stored path is never serialised anywhere,
     * so no URL to the private disk can leak into a client.
     */
    public function test_a_payment_proof_path_is_never_exposed_in_a_booking_body(): void
    {
        Storage::fake(config('booking.proof_disk'));

        $user = User::factory()->create();
        $staycation = Staycation::factory()->create();

        $booking = Booking::factory()->for($user)->for($staycation)->create([
            'payment_proof' => 'payment_proofs/secret-receipt.png',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/bookings/{$booking->getKey()}");

        $response->assertOk();
        $this->assertStringNotContainsString('payment_proofs/secret-receipt.png', $response->getContent());
        $this->assertStringNotContainsString('secret-receipt', $response->getContent());
    }

    public function test_the_proof_endpoint_serves_the_owner_the_file_itself(): void
    {
        $disk = config('booking.proof_disk');
        Storage::fake($disk);
        Storage::disk($disk)->put('payment_proofs/receipt.png', UploadedFile::fake()->create('receipt.png', 10, 'image/png')->get());

        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->for(Staycation::factory())->create([
            'payment_proof' => 'payment_proofs/receipt.png',
        ]);

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/bookings/{$booking->getKey()}/proof")
            ->assertOk();
    }
}
