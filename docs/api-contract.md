# Dicimulacion Staycation — API Contract

**Version:** `v1` · **Status:** Phase 2A (public read-only surface frozen)
**Base URL:** `{APP_URL}/api/v1`

This document is the agreement between the Laravel API and the Next.js
frontend. A consumer should never have to read the controller source to know
what a response looks like. Anything not written here is not promised.

The API sits on top of the domain services stabilised in Phase 1. It does not
restate business rules; it reads them. `BookingAvailabilityService` decides what
is bookable, `BookingPricingService` decides what a stay costs, and the API
reports their answers.

---

## 1. Conventions

### 1.1 Versioning

Every endpoint lives under `/api/v1`. `v1` may only change in
backward-compatible ways: new fields and new endpoints may be added, existing
fields may not be removed, renamed, or change type. A breaking change opens
`/api/v2` alongside `v1` rather than editing these routes.

### 1.2 Resource identifiers

Staycations are addressed by their **numeric primary key**.

The `staycations` table has no slug column, and every existing web route
(`/booking/{id}`) already addresses properties by id. Adding a slug now would
mean a migration plus a backfill for a benefit no consumer has asked for, and it
would create a second identifier that has to be kept unique forever. A slug can
be introduced later as an *additional* lookup key without breaking a client that
stores ids.

Routes constrain the parameter with `whereNumber`, so `/api/v1/staycations/abc`
does not match the route and returns the generic 404 body.

### 1.3 Response envelopes

Laravel's own resource conventions are used; no custom response framework.

**Single resource — 200**

```json
{ "data": { "...": "..." } }
```

**Collection — 200** (always paginated)

```json
{
  "data": [ { "...": "..." } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "https://api.example.com/api/v1/staycations",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

**Validation failure — 422**

```json
{
  "message": "The start date field is required.",
  "errors": {
    "start_date": ["The start date field is required."],
    "end_date": ["The departure date must be after the arrival date."]
  }
}
```

**Not found — 404**

```json
{ "message": "Staycation not found." }
```

An unknown path under `/api/*` returns `{ "message": "Resource not found." }`.

**Other errors**

| Status | Body | Raised by |
| --- | --- | --- |
| 401 | `{"message": "Unauthenticated."}` | an authenticated endpoint without valid credentials (Phase 2B) |
| 403 | `{"message": "<policy message>"}` | an authorization policy denial (Phase 2B) |
| 405 | `{"message": "..."}` | wrong HTTP method on a known path |
| 422 | `{"message": "<rule message>"}` | a `BookingRuleViolation` — a well-formed request the domain refuses |
| 429 | `{"message": "Too Many Attempts."}` | rate limit exceeded |
| 500 | `{"message": "Server Error"}` | unexpected failure; never a stack trace in production |

### 1.4 Always JSON

Every response under `/api/*` is JSON, decided by **path**, not by the `Accept`
header. A browser `fetch()` sends a wildcard `Accept` header by default, and a
client that omits the header still receives JSON rather than a Blade error page.
Configured in `bootstrap/app.php` via `shouldRenderJsonWhen`. Web routes keep
Laravel's default behaviour and are unaffected.

Model-not-found is rendered explicitly so the response cannot leak the internal
model class name (`No query results for model [App\Models\Staycation] 5`) or a
stack trace.

### 1.5 Money

Every monetary value is published twice:

- `*_price` / `*_fee` — an exact decimal **string**, e.g. `"4500.00"`, for display.
- `*_centavos` — an **integer** number of centavos, e.g. `450000`, for arithmetic.

A frontend that multiplies the decimal string as a float would reintroduce
exactly the drift `App\Support\Money` exists to prevent. Use the centavos.

Currency is `PHP` throughout and is published on each resource as `currency`.

### 1.6 Dates

All dates are `YYYY-MM-DD` strings in the application's timezone. Timestamps are
not exposed on public resources.

**Booking date convention (Phase 1, unchanged):** a booking occupies the nights
`[start_date, end_date)`. `end_date` is the checkout day and stays bookable, so
`10 → 15` and `15 → 20` do **not** conflict.

**Blocked-date convention (Phase 1, unchanged):** a blocked range is
**inclusive** of both ends, so a block of `10 → 12` removes the nights of the
10th, 11th and 12th.

### 1.7 Rate limiting

`/api/*` is throttled to **60 requests per minute** per caller (per authenticated
user, or per IP for guests), via the `api` limiter defined in
`AppServiceProvider`. Counting runs through the configured cache store, so no
Redis is required on shared hosting.

Exceeding the budget returns **429** with the framework's own body:

```json
{ "message": "Too Many Attempts." }
```

That wording is Laravel's, not this application's. It is documented as-is
rather than overridden: customising the framework's throttle response to match
a prettier sentence would be a change to runtime behaviour made purely to serve
documentation. If a product reason to reword it ever appears, the override and
this section change together.

**Rate-limit headers are conditional. Do not treat them as guaranteed on every
API response.** They are attached by the throttle middleware as a response
passes back out through it, so only a request that actually reaches and
completes throttle processing carries them.

| Header | When present | Meaning |
| --- | --- | --- |
| `X-RateLimit-Limit` | responses processed by the API throttle middleware | the budget, currently `60` |
| `X-RateLimit-Remaining` | responses processed by the API throttle middleware | calls left in the window |
| `Retry-After` | limiter-generated `429` | seconds until the window resets |
| `X-RateLimit-Reset` | limiter-generated `429` | Unix timestamp of the reset |

A limiter-generated `429` carries the applicable rate-limit metadata, including
`Retry-After` and `X-RateLimit-Reset`.

A request rejected **before** normal throttle processing may carry none of
these headers. Depending on middleware ordering, that includes at least:

- an unmatched path under `/api/*` — routing fails before route middleware runs
- an unsupported method on a known path — likewise resolved before the throttle
- an authentication failure such as an unauthenticated `GET /api/user` — the
  failure propagates past the throttle middleware as an exception rather than
  as a response it can decorate

Those responses are still JSON and still carry the statuses in §1.3; they
simply lack the `X-RateLimit-*` family. A client must therefore treat these
headers as optional and read them defensively, rather than keying its retry
logic on their presence.

This is a documentation clarification only. The throttling implementation is
correct as it stands and is deliberately **not** changed to force headers onto
responses the middleware never sees.

---

## 2. Endpoints

### 2.1 List staycations — **IMPLEMENTED**

| | |
| --- | --- |
| **Method** | `GET` |
| **Path** | `/api/v1/staycations` |
| **Auth** | none (public) |
| **Route name** | `api.v1.staycations.index` |

**Query parameters**

| Name | Type | Required | Default | Notes |
| --- | --- | --- | --- | --- |
| `page` | integer ≥ 1 | no | `1` | |
| `per_page` | integer 1–50 | no | `15` | above 50 is a 422, not a silent clamp |

**Success — 200**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Seaside Retreat",
      "description": "Two-storey house facing the cove...",
      "location": "Cebu City",
      "currency": "PHP",
      "price_per_night": "4500.00",
      "price_per_night_centavos": 450000,
      "availability_status": "available",
      "is_bookable": true,
      "capacity": {
        "included_guests": 6,
        "maximum_guests": 8,
        "extra_guest_fee": "500.00",
        "extra_guest_fee_centavos": 50000
      },
      "rating": { "average": 4.5, "count": 12 },
      "image_url": "https://api.example.com/storage/staycations/main.jpg",
      "gallery": ["https://api.example.com/storage/staycations/gallery/one.jpg"]
    }
  ],
  "links": { "...": "..." },
  "meta": { "...": "..." }
}
```

**Validation errors — 422** — `per_page` outside 1–50, `page` below 1.

**Business rule notes**

- Only staycations with `house_availability = "available"` are listed. This
  matches what the Blade homepage has always shown: a property an administrator
  has taken offline is not advertised. Every listed item therefore has
  `is_bookable: true`.
- Ordered by `id` ascending, so pagination is stable.
- Images are eager-loaded and review ratings are aggregated in the query, so
  page size does not change the number of database round trips.
- `rating.average` is `null` when a staycation has no reviews; `rating.count` is
  then `0`. Ratings are aggregated through bookings, and no reviewer identity is
  exposed.

### 2.2 Show a staycation — **IMPLEMENTED**

| | |
| --- | --- |
| **Method** | `GET` |
| **Path** | `/api/v1/staycations/{staycation}` |
| **Auth** | none (public) |
| **Route name** | `api.v1.staycations.show` |

**Path parameters** — `staycation`: integer id.

**Success — 200** — `{ "data": { ... } }`, the same object shape as §2.1.

**Not found — 404** — `{ "message": "Staycation not found." }`

**Business rule notes**

- Unlike the listing, this endpoint **does** answer for a property that has been
  taken offline, returning `availability_status: "unavailable"` and
  `is_bookable: false`. A link a guest already holds keeps resolving instead of
  turning into a 404, and the frontend can render an "unavailable" state.
- `image_url` is `null` when no main image is set. `gallery` is `[]` when there
  are no gallery images — the key is always present.
- Image paths live beneath `public/storage` and are published as absolute URLs.
  No private-disk path is ever exposed. Payment proofs, internal notes, admin
  columns and timestamps are not part of this resource.

### 2.3 Check availability — **IMPLEMENTED**

| | |
| --- | --- |
| **Method** | `GET` |
| **Path** | `/api/v1/staycations/{staycation}/availability` |
| **Auth** | none (public) |
| **Route name** | `api.v1.staycations.availability` |

**Query parameters**

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `start_date` | `YYYY-MM-DD` | yes | arrival; must be today or later |
| `end_date` | `YYYY-MM-DD` | yes | checkout; must be strictly after `start_date` |

`date_format` is enforced rather than Laravel's looser `date`: a published
contract should tell a client that `next friday` is not a date rather than
silently interpreting it.

**Success — 200**

```json
{
  "data": {
    "staycation_id": 1,
    "start_date": "2026-09-10",
    "end_date": "2026-09-14",
    "nights": 4,
    "available": true,
    "unavailable_reasons": [],
    "reserves_inventory": false
  }
}
```

**`unavailable_reasons`** — every applicable reason is reported, in this order:

| Code | Meaning |
| --- | --- |
| `property_unavailable` | the property is closed to new bookings entirely |
| `booking_conflict` | another booking already holds a night in the range |
| `blocked_dates` | an administrator has blocked a night in the range |

`available` is `true` exactly when `unavailable_reasons` is empty.

**Validation errors — 422**

| Field | Rule |
| --- | --- |
| `start_date` | `required`, `date_format:Y-m-d`, `after_or_equal:today` |
| `end_date` | `required`, `date_format:Y-m-d`, `after:start_date` |

**Not found — 404** — `{ "message": "Staycation not found." }`

**Business rule notes**

- **This is a read, not a hold.** `reserves_inventory` is always `false` and is
  part of the contract so the meaning cannot quietly drift. Nothing is created,
  no lock is taken, and the range can be claimed by another guest a moment
  later.
- Booking creation (Phase 2B) re-checks the same rules inside a transaction
  holding the staycation's write lock. **That** check decides whether a stay
  happens; an `available: true` here is a hint for the UI only. A frontend must
  handle a booking attempt failing after a successful availability check.
- The checkout day is reusable: against a booking of `9 → 14`, a request for
  `14 → 19` is available.
- A blocked range is inclusive: against a block of `10 → 12`, a request for
  `12 → 15` is refused and `13 → 15` is available.
- Bookings with status `declined` or `cancelled` release their dates.
- `nights` is computed by `BookingPricingService::nights()` and is never fewer
  than one.

### 2.4 Planned endpoints — **NOT YET IMPLEMENTED**

These are named so the frontend can plan against them. Shapes are **not** frozen
and must not be coded against until they move to IMPLEMENTED.

| Method | Path | Purpose | Phase |
| --- | --- | --- | --- |
| `POST` | `/api/v1/staycations/{staycation}/quote` | price a stay for a guest count without creating anything | 2B |
| `GET` | `/api/v1/staycations/{staycation}/calendar` | occupied and blocked nights for a month, for a date picker | 2B |
| `POST` | `/api/v1/bookings` | create a booking with payment proof | 2B |
| `GET` | `/api/v1/bookings` | the authenticated guest's own bookings | 2B |
| `GET` | `/api/v1/bookings/{booking}` | one booking the guest owns | 2B |
| `DELETE` | `/api/v1/bookings/{booking}` | cancel a booking the guest owns | 2B |
| `GET` | `/api/v1/staycations/{staycation}/reviews` | public reviews for a property | 2B |
| — | `/api/v1/admin/*` | administration surface | 2C, not before |

Also planned but not implemented: a listing filter for offline properties
(`?availability=`), and slug-based lookup alongside numeric ids.

---

## 3. CORS

The frontend is deployed to Vercel and the API to Hostinger, so every browser
call is cross-origin. Configuration lives in `config/cors.php` and is entirely
environment-driven; no hostname is written into the repository.

| Variable | Meaning |
| --- | --- |
| `FRONTEND_ORIGINS` | comma-separated absolute origins, scheme included, no trailing slash |
| `FRONTEND_ORIGIN_PATTERNS` | comma-separated regular expressions, for preview deployments whose hostname changes per build (a pattern may not itself contain a comma) |
| `CORS_SUPPORTS_CREDENTIALS` | `false` while the API is token-less; see §4 |
| `CORS_MAX_AGE` | seconds a browser may cache a preflight response |

Settings:

- `paths` — `api/*` and `sanctum/csrf-cookie` only. CORS is not opened on the
  Blade application.
- `allowed_methods` — `GET, POST, PUT, PATCH, DELETE, OPTIONS`.
- `allowed_headers` — named explicitly (`Accept`, `Authorization`,
  `Content-Type`, `Origin`, `X-Requested-With`, `X-XSRF-TOKEN`) rather than `*`.
  The CORS specification does not treat `*` as a wildcard once credentials are
  involved, so an explicit list is the only form that keeps working if Phase 2B
  turns credentials on.
- **Default is closed.** With `FRONTEND_ORIGINS` unset, no cross-origin browser
  call is allowed. An unconfigured deployment refuses rather than accepting
  every site on the internet.

**Wildcard and credentials are never combined.** A browser rejects
`Access-Control-Allow-Origin: *` on a credentialed request, and a server that
worked around that by echoing the caller's origin would let any site ride a
signed-in session. `config/cors.php` drops a `*` origin whenever
`CORS_SUPPORTS_CREDENTIALS` is on, which fails closed.

Local development, in `.env` (not committed):

```
FRONTEND_ORIGINS=http://localhost:3000
```

Production sets the real Vercel or custom-domain origin the same way.

---

## 4. AUTH STRATEGY DECISION

**Status: documented, deliberately NOT implemented.** The choice below depends
on a domain decision that has not been made, and picking wrong means either a
broken login or an insecure one.

Precisely what "not implemented" means here:

- Phase 2A adds **no new authenticated business endpoint**, and no endpoint in
  §2 requires or accepts credentials.
- Phase 2A changes nothing about how anyone authenticates. The Blade
  application's Fortify/Jetstream session login is untouched.
- **One authenticated route already exists and predates this phase:**
  `GET /api/user`, the Laravel starter stub, protected by `auth:sanctum`. It
  was in `routes/api.php` before Phase 2A and is left exactly as it was. It is
  **not** part of the `v1` public business contract, is not versioned, and a
  frontend should not build against it. Whether it survives into Phase 2B is
  part of the decision below; it is not deleted or redesigned in this phase.
  (It is used in the test suite as a convenient real `auth:sanctum` route for
  asserting the JSON 401 contract — see §6.)

### 4.1 What exists today

| | |
| --- | --- |
| Web authentication | Fortify + Jetstream, session cookies, guard `web` |
| Sanctum | v4, installed; `config/sanctum.php` guard `web`, stateful domains from `SANCTUM_STATEFUL_DOMAINS` |
| Personal access tokens | `User` uses `HasApiTokens`, so tokens are issuable in code — but Jetstream's `Features::api()` is **disabled**, so there is no token-management UI and no token is issued anywhere today |
| Sessions | `SESSION_DRIVER=database`, `SESSION_SAME_SITE=lax`, `SESSION_DOMAIN` unset |
| Roles | admin / staff / customer, enforced by `AdminMiddleware`, `StaffMiddleware` and the Phase 1 policies |
| API today | `/api/user` (the framework stub, `auth:sanctum`) plus the three public `v1` endpoints |

`SESSION_SAME_SITE=lax` is the decisive fact: a `lax` cookie is **not** sent on
cross-site XHR. Cookie authentication from a `*.vercel.app` frontend cannot work
without changing this, and changing it has consequences for the existing Blade
application.

### 4.2 Option A — same parent domain, Sanctum SPA cookie mode

Deploy the frontend and the API as siblings:

```
app.dicimulacion.example   →  Next.js on Vercel (custom domain)
api.dicimulacion.example   →  Laravel on Hostinger
```

Because both are `*.dicimulacion.example`, the session cookie is **same-site**.

#### Required Phase 2B implementation step — NOT DONE IN PHASE 2A

Sanctum's SPA cookie mode does not work merely by configuring domains. The API
routes must run Sanctum's stateful middleware, which in Laravel 12 is enabled in
`bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->statefulApi();   // NOT present in Phase 2A
})
```

`statefulApi()` prepends `EnsureFrontendRequestsAreStateful` to the `api`
group, which is what makes the session cookie, the `web` guard and CSRF apply to
`/api/*` for requests from a stateful domain.

**This is not enabled today.** `bootstrap/app.php` currently calls only
`$middleware->alias(...)` and `$middleware->throttleApi()`. Phase 2A ships no
stateful API middleware, and it must not be added speculatively: turning it on
changes how *every* `/api/*` request is authenticated and session-handled, and
it is only correct if Option A is the chosen architecture.

If Option A is selected, Phase 2B must implement all of the following together —
each one alone leaves either a broken login or an open session:

| Area | What Phase 2B must set | Why |
| --- | --- | --- |
| Stateful middleware | `$middleware->statefulApi()` | without it, `/api/*` never sees the session cookie |
| Stateful domains | `SANCTUM_STATEFUL_DOMAINS=app.dicimulacion.example` | the allowlist `statefulApi()` checks the request against |
| Session domain | `SESSION_DOMAIN=.dicimulacion.example` | so the cookie is issued for the shared parent domain |
| Secure cookies | `SESSION_SECURE_COOKIE=true` in production | a session cookie must never travel over plain HTTP |
| CORS credentials | `CORS_SUPPORTS_CREDENTIALS=true` and `FRONTEND_ORIGINS=https://app.dicimulacion.example` | the browser sends cookies cross-origin only with an explicit credentialed grant; see §3 — the wildcard is stripped automatically |
| CSRF flow | frontend calls `GET /sanctum/csrf-cookie` first, then sends `X-XSRF-TOKEN` on every mutating request | cookie auth without CSRF protection is CSRF-vulnerable by construction |

`SESSION_SAME_SITE` stays `lax`, which is sufficient once both hosts share a
parent domain.

None of the above is implemented, configured, or partially prepared in Phase 2A.

- **Pro** — no token is ever exposed to JavaScript; the session cookie stays
  `HttpOnly`. Reuses the existing Fortify login, including two-factor. CSRF is
  handled by the framework. Least new code and the smallest attack surface.
- **Pro** — the Blade admin application keeps working unchanged, on the same
  session.
- **Con** — requires buying/pointing a custom domain and attaching it to the
  Vercel project. Vercel **preview** deployments are on `*.vercel.app` and are
  cross-site, so previews cannot log in; they would need a separate mechanism or
  a preview-only subdomain.

**This is the recommended option** if a custom domain is acceptable.

### 4.3 Option B — cross-site frontend, explicitly designed token or BFF strategy

If the frontend stays on `*.vercel.app`, cookies cannot be shared and a token
mechanism is required. Two sub-options, in order of preference:

**B1 — Backend-for-frontend (BFF).** The browser talks only to Next.js Route
Handlers on its own origin; those handlers hold the Laravel token server-side
(in an encrypted, `HttpOnly`, `SameSite=Lax` cookie or a server session) and
call Laravel from the server. The browser never sees a Laravel credential, and
CORS becomes irrelevant because the browser makes no cross-origin call.

- **Pro** — no token in JavaScript, works on any Vercel domain including
  previews, and the Laravel API can require a bearer token unconditionally.
- **Con** — a second server-side component to build, deploy and secure; every
  request pays an extra hop; the Next.js deployment now holds a secret.

**B2 — Sanctum personal access tokens held in memory.** Laravel issues a
short-lived token (`sanctum.expiration` set, currently `null` = never expires)
on login; the frontend keeps it in a JavaScript variable and refreshes on reload
via a refresh mechanism that must itself be designed.

- **Pro** — simplest to implement on the Laravel side.
- **Con** — the token is reachable by any script in the page, so a single XSS in
  the frontend is a full account takeover. Requires token expiry, revocation and
  a refresh path, none of which exist today.

**Explicitly ruled out:** storing a bearer token in `localStorage` or
`sessionStorage`, and any wildcard CORS origin combined with credentials.

### 4.4 Decision required before Phase 2B

1. Will the frontend get a custom domain sharing a parent with the API
   (**Option A**), or stay on `*.vercel.app` (**Option B**)?
2. If B: BFF (**B1**, recommended) or in-memory tokens (**B2**)?
3. Do Vercel preview deployments need to authenticate at all?
4. Does the admin surface move to the API, or stay in Blade? Keeping admin in
   Blade removes the highest-value target from the cross-origin surface.
5. What becomes of the pre-existing `GET /api/user` stub — promoted to a
   documented `v1` endpoint, replaced, or removed? It is left untouched in
   Phase 2A precisely because that answer follows from 1 and 2.

Until 1 and 2 are answered, no authenticated endpoint should be written, and
`$middleware->statefulApi()` must not be enabled (see §4.2).

---

## 5. Deployment notes

Hostinger shared hosting. Nothing in this phase requires Redis, Horizon, Octane,
WebSockets, Reverb, Docker, or a persistent worker process. Rate limiting counts
through the configured cache store; sessions stay in the database.

---

## 6. Test coverage

| File | Covers |
| --- | --- |
| `tests/Feature/ApiStaycationCatalogueTest.php` | list envelope and pagination, offline properties hidden from the list but resolvable by id, gallery and null-image shapes, review aggregation, JSON 404 for a missing staycation and for an unknown path, JSON without an `Accept` header, no internal columns in the payload |
| `tests/Feature/ApiStaycationAvailabilityTest.php` | free range, booking overlap, checkout-day reuse in both directions, released dates on cancellation, inclusive blocked ranges, offline property, all reasons at once, every validation rule, the 422 envelope, JSON 404 |
| `tests/Feature/ApiCorsTest.php` | closed by default, configured origin allowed, foreign origin refused with `Vary: Origin`, preflight answered, CORS scoped to `api/*`; and — by rebuilding `config/cors.php` from a deliberately dangerous environment — that a `*` origin is stripped when credentials are on, that no credentialed response grants every origin, and (as a control) that a `*` origin still works while credentials are off |
| `tests/Feature/ApiErrorContractTest.php` | every status in §1.3 — 401, 403, 405, 429, 500 and a domain 422 — asserted **without** asking for JSON, with `app.debug` forced off; that a 500 leaks neither the exception message, class, file path nor trace; and that the Blade application still returns HTML 404s and redirecting validation failures |

Two properties are guarded by construction rather than by assertion wording:

- **No test in `ApiErrorContractTest` uses `getJson()`.** Every request carries
  the browser HTML accept header, which is the exact case `expectsJson()` gets
  wrong. Using `getJson()` there would make the file pass without the
  path-based rule existing at all.
- **The CORS safety tests re-evaluate `config/cors.php` itself** rather than
  reading the already-built `config('cors')` array, so they fail if the strip
  is removed. A control test proves the strip is conditional and not vacuous.

Phase 1 suites are unchanged and continue to own the domain rules themselves.
