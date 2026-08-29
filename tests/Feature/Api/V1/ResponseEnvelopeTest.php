<?php

namespace Tests\Feature\Api\V1;

use App\Models\Staycation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Locks the response envelope described in docs/api-contract.md. The Next.js
 * client branches on these keys, so a change here is a breaking API change and
 * should fail loudly rather than silently reshape the payload.
 */
class ResponseEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_single_resource_is_wrapped_in_success_and_data(): void
    {
        $staycation = Staycation::factory()->create();

        $this->getJson("/api/v1/staycations/{$staycation->getKey()}")
            ->assertOk()
            ->assertJsonStructure(['success', 'data'])
            ->assertJsonPath('success', true);
    }

    public function test_a_paginated_collection_carries_meta_and_links(): void
    {
        Staycation::factory()->count(3)->create();

        $this->getJson('/api/v1/staycations?per_page=2')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ])
            ->assertJsonPath('links.prev', null);
    }

    public function test_a_validation_failure_reports_success_false_with_errors(): void
    {
        $staycation = Staycation::factory()->create();

        $this->postJson("/api/v1/staycations/{$staycation->getKey()}/quote", [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_a_domain_failure_reports_success_false_with_an_error_code(): void
    {
        $staycation = Staycation::factory()->create();

        $this->postJson("/api/v1/staycations/{$staycation->getKey()}/quote", [
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDays(8)->toDateString(),
            'guest_number' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'invalid_dates');
    }

    public function test_an_unauthenticated_failure_uses_the_envelope(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'unauthenticated');
    }

    public function test_a_not_found_failure_uses_the_envelope(): void
    {
        $this->getJson('/api/v1/staycations/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'not_found');
    }

    public function test_a_created_resource_carries_a_message(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/admin/staycations', [
            'house_name' => 'Seaside Villa',
            'house_description' => 'A lovely place.',
            'house_price' => 4200,
            'house_location' => 'Batangas',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'message']);
    }

    /**
     * Money must serialise as a two-decimal string so the client never has to
     * deal with float representation.
     */
    public function test_money_is_returned_as_two_decimal_strings(): void
    {
        $staycation = Staycation::factory()->pricedAt(3000)->create();

        $response = $this->postJson("/api/v1/staycations/{$staycation->getKey()}/quote", [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-13',
            'guest_number' => 2,
        ])->assertOk();

        foreach (['price_per_night', 'accommodation_total', 'total_price', 'deposit_amount', 'balance_due'] as $field) {
            $value = $response->json("data.quote.{$field}");

            $this->assertIsString($value, "{$field} should be a string");
            $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $value, "{$field} should have two decimals");
        }
    }
}
