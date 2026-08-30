<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CORS is what stands between the API and every other site a guest has open,
 * so the rules are asserted rather than assumed.
 *
 * Two kinds of test live here.
 *
 * The allow/deny/preflight tests set `cors.allowed_origins` directly, because
 * what they exercise is the middleware's behaviour for a given list.
 *
 * The safety tests instead rebuild config/cors.php from a chosen environment
 * via `buildCorsConfigFrom()`, because what they exercise is the *file's own*
 * transformation — the rule that a wildcard origin can never ship alongside
 * credentials. Asserting on the already-built `config('cors')` array would only
 * restate whatever the current .env happens to say and would prove nothing.
 */
class ApiCorsTest extends TestCase
{
    use RefreshDatabase;

    private const ALLOWED_ORIGIN = 'https://frontend.example.test';

    private const FOREIGN_ORIGIN = 'https://attacker.example.test';

    public function test_no_origin_is_allowed_until_one_is_configured(): void
    {
        config()->set('cors.allowed_origins', []);

        $this->getJson('/api/v1/staycations', ['Origin' => self::ALLOWED_ORIGIN])
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_a_configured_origin_receives_the_allow_header(): void
    {
        config()->set('cors.allowed_origins', [self::ALLOWED_ORIGIN]);

        $this->getJson('/api/v1/staycations', ['Origin' => self::ALLOWED_ORIGIN])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
    }

    /**
     * With more than one origin configured — production plus a preview
     * deployment, the shape this project will actually ship — the allowed
     * origin is chosen per request, and a caller that is not on the list gets
     * no grant at all.
     *
     * `Vary: Origin` must accompany that, or a shared cache could hand one
     * origin the response authorised for another.
     */
    public function test_an_origin_that_is_not_configured_is_refused(): void
    {
        config()->set('cors.allowed_origins', [self::ALLOWED_ORIGIN, 'https://preview.example.test']);

        $this->getJson('/api/v1/staycations', ['Origin' => self::FOREIGN_ORIGIN])
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin')
            ->assertHeader('Vary', 'Origin');
    }

    /**
     * A single configured origin is published verbatim, so the grant names that
     * origin and never the caller: a foreign site reading the header sees an
     * origin that is not its own and is blocked by the browser.
     */
    public function test_a_single_configured_origin_is_never_echoed_back_to_a_foreign_caller(): void
    {
        config()->set('cors.allowed_origins', [self::ALLOWED_ORIGIN]);

        $this->getJson('/api/v1/staycations', ['Origin' => self::FOREIGN_ORIGIN])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
    }

    public function test_a_preflight_request_is_answered_for_a_configured_origin(): void
    {
        config()->set('cors.allowed_origins', [self::ALLOWED_ORIGIN]);

        $this->call('OPTIONS', '/api/v1/staycations', server: [
            'HTTP_ORIGIN' => self::ALLOWED_ORIGIN,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
    }

    /**
     * Rebuild config/cors.php against a chosen environment, the way the
     * framework does at boot.
     *
     * The safety rule under test lives in the config file itself, so asserting
     * on the already-built `config('cors')` array would only restate whatever
     * the current .env happens to say. Re-evaluating the file with deliberately
     * dangerous input is the only way to prove the transformation runs.
     *
     * @param  array<string, string>  $environment
     * @return array<string, mixed>
     */
    private function buildCorsConfigFrom(array $environment): array
    {
        $originalServer = [];
        $originalEnv = [];

        foreach ($environment as $key => $value) {
            $originalServer[$key] = $_SERVER[$key] ?? null;
            $originalEnv[$key] = $_ENV[$key] ?? null;

            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
            putenv($key.'='.$value);
        }

        try {
            return require base_path('config/cors.php');
        } finally {
            foreach ($environment as $key => $value) {
                if ($originalServer[$key] === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $originalServer[$key];
                }

                if ($originalEnv[$key] === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $originalEnv[$key];
                }

                putenv($key);
            }
        }
    }

    /**
     * The dangerous combination, built from the environment that would produce
     * it: a deployment that names `*` as an allowed origin *and* turns
     * credentials on.
     *
     * A browser rejects `Access-Control-Allow-Origin: *` on a credentialed
     * request, and a server that worked around that by echoing the caller would
     * let any site ride a signed-in session. config/cors.php must drop the
     * wildcard rather than emit either form.
     */
    public function test_a_wildcard_origin_is_stripped_when_credentials_are_enabled(): void
    {
        $cors = $this->buildCorsConfigFrom([
            'FRONTEND_ORIGINS' => '*,'.self::ALLOWED_ORIGIN,
            'CORS_SUPPORTS_CREDENTIALS' => 'true',
        ]);

        $this->assertTrue($cors['supports_credentials']);
        $this->assertNotContains('*', $cors['allowed_origins']);
        $this->assertContains(self::ALLOWED_ORIGIN, $cors['allowed_origins']);
    }

    /**
     * The same configuration, driven through a real request: whatever the
     * environment asked for, no response may grant every origin while
     * credentials are on.
     */
    public function test_a_credentialled_response_never_grants_every_origin(): void
    {
        config()->set('cors', $this->buildCorsConfigFrom([
            'FRONTEND_ORIGINS' => '*,'.self::ALLOWED_ORIGIN.',https://preview.example.test',
            'CORS_SUPPORTS_CREDENTIALS' => 'true',
        ]));

        $foreign = $this->get('/api/v1/staycations', ['Origin' => self::FOREIGN_ORIGIN])->assertOk();

        $foreign->assertHeaderMissing('Access-Control-Allow-Origin')
            ->assertHeaderMissing('Access-Control-Allow-Credentials')
            ->assertHeader('Vary', 'Origin');

        $allowed = $this->get('/api/v1/staycations', ['Origin' => self::ALLOWED_ORIGIN])->assertOk();

        $this->assertNotSame('*', $allowed->headers->get('Access-Control-Allow-Origin'));

        $allowed->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * The wildcard is stripped even when it is the *only* origin configured.
     *
     * This is the worst case, because the list then collapses to empty and the
     * library's single-origin shortcut would otherwise publish whatever
     * survived verbatim. Nothing may be granted at all.
     */
    public function test_a_lone_wildcard_leaves_nothing_granted_when_credentials_are_enabled(): void
    {
        $cors = $this->buildCorsConfigFrom([
            'FRONTEND_ORIGINS' => '*',
            'CORS_SUPPORTS_CREDENTIALS' => 'true',
        ]);

        $this->assertTrue($cors['supports_credentials']);
        $this->assertSame([], $cors['allowed_origins']);

        config()->set('cors', $cors);

        $this->get('/api/v1/staycations', ['Origin' => self::FOREIGN_ORIGIN])
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    /**
     * The control for the two tests above.
     *
     * Without this, a config file that dropped `*` unconditionally — or one
     * that never read FRONTEND_ORIGINS at all — would pass them. A wildcard is
     * legitimate and is preserved while credentials are off, and the response
     * then carries no `Access-Control-Allow-Credentials` header at all.
     */
    public function test_a_wildcard_origin_survives_while_credentials_are_disabled(): void
    {
        $cors = $this->buildCorsConfigFrom([
            'FRONTEND_ORIGINS' => '*',
            'CORS_SUPPORTS_CREDENTIALS' => 'false',
        ]);

        $this->assertFalse($cors['supports_credentials']);
        $this->assertSame(['*'], $cors['allowed_origins']);

        config()->set('cors', $cors);

        $this->get('/api/v1/staycations', ['Origin' => self::FOREIGN_ORIGIN])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    /**
     * An unconfigured deployment must fail closed rather than inherit the
     * framework default of allowing every origin.
     */
    public function test_an_unconfigured_environment_allows_no_origin(): void
    {
        $cors = $this->buildCorsConfigFrom([
            'FRONTEND_ORIGINS' => '',
            'FRONTEND_ORIGIN_PATTERNS' => '',
            'CORS_SUPPORTS_CREDENTIALS' => 'false',
        ]);

        $this->assertSame([], $cors['allowed_origins']);
        $this->assertSame([], $cors['allowed_origins_patterns']);
    }

    public function test_cors_applies_to_the_api_only(): void
    {
        $this->assertContains('api/*', config('cors.paths'));
        $this->assertNotContains('*', config('cors.paths'));
    }
}
