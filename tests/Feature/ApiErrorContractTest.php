<?php

namespace Tests\Feature;

use App\Exceptions\StaycationUnavailable;
use App\Models\Staycation;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * The error half of the published contract, in docs/api-contract.md §1.3–§1.4.
 *
 * Two properties are asserted throughout, and both are easy to lose to an
 * unrelated change:
 *
 *  1. Every failure under /api/* answers in JSON. Membership is decided by
 *     path, not by the `Accept` header, so **no request in this file asks for
 *     JSON**. `$this->get()` sends the browser HTML accept header Symfony
 *     builds by default, which is exactly the case `expectsJson()` gets wrong.
 *     `getJson()` would make every test here pass for the wrong reason.
 *
 *  2. Nothing internal leaks. `app.debug` is forced off, because that is the
 *     shape production returns and the shape the contract documents; with debug
 *     on, Laravel adds the exception class, file, line and stack trace to every
 *     one of these bodies.
 *
 * The final test guards the other direction: the global `shouldRenderJsonWhen`
 * change must not have turned the Blade application's errors into JSON.
 */
class ApiErrorContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Requests per minute the contract publishes for /api/*.
     *
     * Written out rather than read from the limiter so that changing the budget
     * fails this test and forces the documentation to change with it.
     */
    private const DOCUMENTED_RATE_LIMIT = 60;

    /**
     * Marker that must never reach a client. Any leak of the exception message,
     * however it is framed, contains this string.
     */
    private const SECRET_MARKER = 'SUPER_SECRET_INTERNAL_MESSAGE';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.debug', false);

        /*
         * The 500 case deliberately throws, and reporting it would write the
         * secret marker into the real log file. Nothing here asserts on
         * reporting, so the channel is discarded.
         */
        config()->set('logging.default', 'null');
    }

    /**
     * Register a route for the duration of one test, inside the real `api`
     * middleware group so the request travels the same pipeline a production
     * endpoint would.
     *
     * A route that only exists to be broken has no business being reachable in
     * production, so it is defined here rather than in routes/api.php.
     */
    private function defineApiRoute(string $path, Closure $handler): string
    {
        Route::middleware('api')->prefix('api/v1')->get($path, $handler);

        return '/api/v1/'.ltrim($path, '/');
    }

    /**
     * Assert a response is JSON and carries none of the internals that a debug
     * body would have exposed.
     */
    private function assertJsonWithoutInternals(TestResponse $response): void
    {
        $contentType = (string) $response->headers->get('Content-Type');
        $body = $response->getContent();

        $this->assertStringContainsString('application/json', $contentType);
        $this->assertStringNotContainsString('<!DOCTYPE', $body);
        $this->assertStringNotContainsString('<html', $body);

        foreach (['exception', 'trace', '.php', 'vendor', 'App\\'] as $internal) {
            $this->assertStringNotContainsString($internal, $body);
        }
    }

    public function test_an_unauthenticated_api_request_returns_json_401(): void
    {
        $response = $this->get('/api/user');

        $response->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->assertJsonWithoutInternals($response);
    }

    public function test_an_authorization_failure_returns_json_403(): void
    {
        $path = $this->defineApiRoute('__test/forbidden', function (): never {
            throw new AuthorizationException('You may not view this booking.');
        });

        $response = $this->get($path);

        $response->assertForbidden()
            ->assertExactJson(['message' => 'You may not view this booking.']);

        $this->assertJsonWithoutInternals($response);
    }

    public function test_an_unsupported_method_returns_json_405(): void
    {
        $response = $this->post('/api/v1/staycations');

        $response->assertStatus(405)
            ->assertJsonStructure(['message']);

        $this->assertJsonWithoutInternals($response);
    }

    /**
     * The contract publishes both the budget and the body Laravel actually
     * returns. The framework's wording is `Too Many Attempts.`; the contract
     * follows the framework rather than the framework being customised to
     * follow stale documentation.
     */
    public function test_exhausting_the_rate_limiter_returns_json_429(): void
    {
        for ($attempt = 1; $attempt <= self::DOCUMENTED_RATE_LIMIT; $attempt++) {
            $allowed = $this->get('/api/v1/staycations')->assertOk();
        }

        $allowed->assertHeader('X-RateLimit-Limit', self::DOCUMENTED_RATE_LIMIT)
            ->assertHeader('X-RateLimit-Remaining', 0);

        $response = $this->get('/api/v1/staycations');

        $response->assertStatus(429)
            ->assertExactJson(['message' => 'Too Many Attempts.'])
            ->assertHeader('X-RateLimit-Limit', self::DOCUMENTED_RATE_LIMIT);

        $this->assertNotNull($response->headers->get('Retry-After'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
        $this->assertJsonWithoutInternals($response);
    }

    /**
     * An unexpected failure must publish nothing but a generic message: not the
     * exception text, not its class, not a path from this machine, not a frame
     * of the stack.
     */
    public function test_an_unexpected_failure_returns_json_500_without_leaking_internals(): void
    {
        $path = $this->defineApiRoute('__test/explode', function (): never {
            throw new RuntimeException(self::SECRET_MARKER);
        });

        $response = $this->get($path);

        $response->assertStatus(500)
            ->assertExactJson(['message' => 'Server Error']);

        $this->assertStringNotContainsString(self::SECRET_MARKER, $response->getContent());
        $this->assertStringNotContainsString('RuntimeException', $response->getContent());
        $this->assertStringNotContainsString(base_path(), $response->getContent());
        $this->assertJsonWithoutInternals($response);
    }

    /**
     * A domain refusal is a different thing from an unexpected failure: the
     * request was well formed and the rule said no. Phase 1 wrote these
     * messages for the person who triggered them, so unlike a 500 the message
     * is published verbatim.
     */
    public function test_a_domain_rule_violation_returns_json_422_with_its_message(): void
    {
        $staycation = Staycation::factory()->create(['house_name' => 'Seaside Retreat']);

        $path = $this->defineApiRoute('__test/domain-refusal', function () use ($staycation): never {
            throw StaycationUnavailable::datesTaken($staycation->house_name);
        });

        $response = $this->get($path);

        $response->assertStatus(422)
            ->assertExactJson([
                'message' => 'The selected dates are no longer available for Seaside Retreat.',
            ]);

        $this->assertJsonWithoutInternals($response);
    }

    /**
     * The strongest form of the path-over-header rule: a client that explicitly
     * asks for HTML still gets JSON from the API.
     */
    public function test_api_errors_stay_json_even_when_html_is_requested(): void
    {
        $response = $this->get('/api/v1/staycations/999999', ['Accept' => 'text/html']);

        $response->assertNotFound()
            ->assertExactJson(['message' => 'Staycation not found.']);

        $this->assertJsonWithoutInternals($response);
    }

    /**
     * The other direction, and the reason `shouldRenderJsonWhen` is scoped to
     * `api/*` rather than made unconditional: the Blade application must keep
     * rendering HTML error pages.
     */
    public function test_a_missing_web_route_still_returns_html(): void
    {
        $response = $this->get('/this-web-route-does-not-exist');

        $response->assertNotFound();

        $this->assertStringContainsString(
            'text/html',
            (string) $response->headers->get('Content-Type'),
        );
        $this->assertNull(json_decode($response->getContent(), true));
    }

    public function test_a_web_validation_failure_still_redirects_rather_than_returning_json(): void
    {
        $response = $this->from('/login')->post('/login', []);

        $response->assertRedirect('/login')
            ->assertSessionHasErrors();
    }
}
