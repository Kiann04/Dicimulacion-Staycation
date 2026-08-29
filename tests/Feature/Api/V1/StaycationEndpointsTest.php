<?php

namespace Tests\Feature\Api\V1;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaycationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_is_paginated_and_public(): void
    {
        Staycation::factory()->count(3)->create();

        $this->getJson('/api/v1/staycations?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_per_page_is_capped_so_a_client_cannot_request_the_whole_table(): void
    {
        Staycation::factory()->count(3)->create();

        $this->getJson('/api/v1/staycations?per_page=100000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);
    }

    public function test_the_catalogue_can_be_filtered_to_available_listings(): void
    {
        Staycation::factory()->create(['house_name' => 'Open House']);
        Staycation::factory()->unavailable()->create(['house_name' => 'Closed House']);

        $this->getJson('/api/v1/staycations?available_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Open House');
    }

    public function test_the_catalogue_can_be_searched(): void
    {
        Staycation::factory()->create(['house_name' => 'Seaside Retreat', 'house_location' => 'Batangas']);
        Staycation::factory()->create(['house_name' => 'Mountain Lodge', 'house_location' => 'Baguio']);

        $this->getJson('/api/v1/staycations?search=Baguio')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mountain Lodge');
    }

    public function test_a_single_staycation_can_be_read(): void
    {
        $staycation = Staycation::factory()->pricedAt(3000)->create();

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.id', $staycation->getKey())
            ->assertJsonPath('data.price_per_night', '3000.00')
            ->assertJsonPath('data.currency', 'PHP')
            ->assertJsonPath('data.is_bookable', true);
    }

    public function test_an_unknown_staycation_returns_a_json_404(): void
    {
        $this->getJson('/api/v1/staycations/999999')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'not_found');
    }

    public function test_availability_reports_a_free_range(): void
    {
        $staycation = Staycation::factory()->create();

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}/availability?start_date=2026-10-10&end_date=2026-10-13")
            ->assertOk()
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.nights', 3)
            ->assertJsonCount(0, 'data.conflicts');
    }

    public function test_availability_reports_conflicts_with_reasons(): void
    {
        $staycation = Staycation::factory()->create();

        Booking::factory()->for($staycation)->forDates('2026-10-11', '2026-10-12')->confirmed()->create();
        BlockedDate::factory()->for($staycation)->forDates('2026-10-12', '2026-10-13')->create(['reason' => 'Maintenance']);

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}/availability?start_date=2026-10-10&end_date=2026-10-13")
            ->assertOk()
            ->assertJsonPath('data.is_available', false)
            ->assertJsonCount(2, 'data.conflicts')
            ->assertJsonPath('data.conflicts.1.reason', 'Maintenance');
    }

    public function test_availability_validates_its_dates(): void
    {
        $staycation = Staycation::factory()->create();

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}/availability?start_date=13-10-2026&end_date=2026-10-10")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_a_quote_is_computed_server_side(): void
    {
        $staycation = Staycation::factory()->pricedAt(3000)->create();

        $this->postJson("/api/v1/staycations/{$staycation->getKey()}/quote", [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-13',
            'guest_number' => 4,
        ])
            ->assertOk()
            ->assertJsonPath('data.quote.nights', 3)
            ->assertJsonPath('data.quote.accommodation_total', '9000.00')
            ->assertJsonPath('data.quote.extra_guests', 0)
            ->assertJsonPath('data.quote.extra_guest_fee', '0.00')
            ->assertJsonPath('data.quote.total_price', '9000.00')
            ->assertJsonPath('data.quote.deposit_amount', '4500.00')
            ->assertJsonPath('data.quote.balance_due', '4500.00')
            ->assertJsonPath('data.is_available', true);
    }

    public function test_a_quote_charges_for_guests_beyond_the_free_threshold(): void
    {
        $staycation = Staycation::factory()->pricedAt(3000)->create();

        $this->postJson("/api/v1/staycations/{$staycation->getKey()}/quote", [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'guest_number' => 8,
        ])
            ->assertOk()
            ->assertJsonPath('data.quote.extra_guests', 2)
            ->assertJsonPath('data.quote.extra_guest_fee', '1000.00')
            ->assertJsonPath('data.quote.total_price', '7000.00');
    }

    public function test_a_deposit_and_balance_always_reconstruct_the_total(): void
    {
        // An odd total exercises the rounding rule: 1 night at 2499.99 plus one
        // extra guest at 500 gives 2999.99, which does not halve cleanly.
        $staycation = Staycation::factory()->pricedAt('2499.99')->create();

        $response = $this->postJson("/api/v1/staycations/{$staycation->getKey()}/quote", [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-11',
            'guest_number' => 7,
        ])->assertOk();

        $quote = $response->json('data.quote');

        $this->assertSame('2999.99', $quote['total_price']);
        $this->assertSame(
            $quote['total_price'],
            bcadd($quote['deposit_amount'], $quote['balance_due'], 2),
        );
    }

    public function test_a_quote_still_reports_a_price_when_the_dates_are_taken(): void
    {
        $staycation = Staycation::factory()->pricedAt(3000)->create();

        Booking::factory()->for($staycation)->forDates('2026-10-10', '2026-10-13')->confirmed()->create();

        $this->postJson("/api/v1/staycations/{$staycation->getKey()}/quote", [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-13',
            'guest_number' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.quote.total_price', '9000.00');
    }

    public function test_a_quote_rejects_more_guests_than_allowed(): void
    {
        $staycation = Staycation::factory()->create();

        $this->postJson("/api/v1/staycations/{$staycation->getKey()}/quote", [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-13',
            'guest_number' => 50,
        ])->assertStatus(422)->assertJsonValidationErrors(['guest_number']);
    }
}
