<?php

namespace Tests\Feature\Api\V1;

use App\Models\Staycation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Next.js client runs on a different origin to the API, so every browser
 * call is preceded by a preflight. These tests pin the behaviour of
 * config/cors.php: an allowed origin is echoed back, an unknown one is not, and
 * the combination that browsers reject outright - a wildcard origin together
 * with credentials - can never be produced by the configuration.
 */
class CorsTest extends TestCase
{
    use RefreshDatabase;

    private function allowOrigin(string ...$origins): void
    {
        config()->set('cors.allowed_origins', $origins);
    }

    public function test_a_preflight_from_the_configured_frontend_is_allowed(): void
    {
        $this->allowOrigin('https://staycation.vercel.app');

        $response = $this->call('OPTIONS', '/api/v1/staycations', server: [
            'HTTP_ORIGIN' => 'https://staycation.vercel.app',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization,content-type',
        ]);

        $response->assertNoContent(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'https://staycation.vercel.app');
    }

    /**
     * With several origins configured the header is resolved per request, so an
     * unknown origin gets no Access-Control-Allow-Origin at all and the browser
     * refuses the response.
     *
     * (With exactly one configured origin and no patterns the underlying library
     * emits that origin statically; the browser still blocks the mismatch, but
     * the header is not a per-request decision, so the dynamic path is what is
     * worth pinning here.)
     */
    public function test_a_preflight_from_an_unknown_origin_is_not_allowed(): void
    {
        $this->allowOrigin('https://staycation.vercel.app', 'http://localhost:3000');

        $response = $this->call('OPTIONS', '/api/v1/staycations', server: [
            'HTTP_ORIGIN' => 'https://attacker.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_an_unknown_origin_is_refused_on_an_actual_request_too(): void
    {
        $this->allowOrigin('https://staycation.vercel.app', 'http://localhost:3000');

        Staycation::factory()->create();

        $response = $this->getJson('/api/v1/staycations', ['Origin' => 'https://attacker.example.com']);

        $response->assertOk();
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_an_actual_request_carries_the_allow_origin_header(): void
    {
        $this->allowOrigin('http://localhost:3000');

        Staycation::factory()->create();

        $this->getJson('/api/v1/staycations', ['Origin' => 'http://localhost:3000'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    }

    public function test_a_preview_deployment_can_be_matched_by_pattern(): void
    {
        $this->allowOrigin('https://staycation.vercel.app');
        config()->set('cors.allowed_origins_patterns', ['/^https:\/\/staycation-[a-z0-9-]+\.vercel\.app$/']);

        $response = $this->call('OPTIONS', '/api/v1/staycations', server: [
            'HTTP_ORIGIN' => 'https://staycation-git-feature-x.vercel.app',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', 'https://staycation-git-feature-x.vercel.app');
    }

    public function test_every_method_the_client_uses_is_permitted(): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
            $this->assertContains($method, config('cors.allowed_methods'), "{$method} is missing from cors.allowed_methods");
        }
    }

    public function test_the_headers_the_client_sends_are_permitted(): void
    {
        $allowed = array_map('strtolower', config('cors.allowed_headers'));

        foreach (['accept', 'content-type', 'authorization'] as $header) {
            $this->assertTrue(
                $allowed === ['*'] || in_array($header, $allowed, true),
                "{$header} is missing from cors.allowed_headers",
            );
        }
    }

    /**
     * A wildcard origin is only safe because credentials are off. If either half
     * of that pair is ever changed the other must change too, so the pair is
     * asserted rather than each value on its own.
     */
    public function test_credentials_are_never_combined_with_a_wildcard_origin(): void
    {
        $this->assertFalse(
            config('cors.supports_credentials') && in_array('*', config('cors.allowed_origins'), true),
            'Allowing credentials with a wildcard origin is rejected by browsers and unsafe.',
        );
    }

    public function test_the_default_configuration_does_not_use_a_wildcard_origin(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));
    }

    public function test_the_api_prefix_is_covered_by_the_cors_paths(): void
    {
        $this->assertContains('api/*', config('cors.paths'));
    }

    public function test_the_local_next_dev_server_is_trusted_when_no_frontend_url_is_set(): void
    {
        // config/cors.php is evaluated with FRONTEND_URL unset in the test
        // environment, which is the same path a fresh clone takes.
        $this->assertContains('http://localhost:3000', config('cors.allowed_origins'));
    }
}
