# Dicimulacion Staycation — REST API v1 Contract

This document describes the API **as implemented** on the Laravel backend. It supersedes
the earlier draft in this file, which described an unversioned `/api/...` surface; the
implemented routes are versioned under `/api/v1` and the response envelope is documented
below. Where the draft and the implementation differed, the implementation is authoritative.

- **Base URL (production)**: `https://<your-hostinger-domain>/api/v1`
- **Base URL (local)**: `http://localhost:8000/api/v1`
- **Content-Type**: `application/json`, or `multipart/form-data` when uploading a payment proof
- **Accept**: `application/json`
- **Authentication**: Sanctum personal access tokens — `Authorization: Bearer <token>`

The Blade application at the same origin is unchanged and continues to serve
`routes/web.php`. Nothing in this API replaces it.

---

## 1. Authentication model

The frontend (Vercel) and the API (Hostinger) sit on **different registrable domains**.
Sanctum's cookie/SPA mode depends on a shared parent domain for its session and XSRF
cookies, so it is not usable here. The API therefore issues **personal access tokens**.

- A token is returned exactly once, by `POST /auth/register` or `POST /auth/login`.
- Send it on every authenticated request as `Authorization: Bearer <token>`.
- Tokens are named per device (`device_name`), so one device can be revoked independently.
- `POST /auth/logout` revokes only the calling token; `POST /auth/logout-all` revokes all.
- Changing the password revokes every *other* token.

### CORS

`config/cors.php` builds its allow list from the environment, so changing the frontend
origin never requires a code change.

| Variable | Purpose |
|---|---|
| `FRONTEND_URL` | The client origin, e.g. `https://your-app.vercel.app`. Falls back to `http://localhost:3000` and `http://127.0.0.1:3000` when unset. |
| `CORS_ALLOWED_ORIGINS` | Extra fixed origins, comma separated. |
| `CORS_ALLOWED_ORIGIN_PATTERNS` | Regular expressions, comma separated — for Vercel preview URLs, which change on every deployment. |

- **Paths**: `api/*`
- **Methods**: `GET, POST, PUT, PATCH, DELETE, OPTIONS`
- **Request headers**: `Accept`, `Authorization`, `Content-Type`, `Origin`, `X-Requested-With`
- **Exposed headers**: `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining` — the
  client needs these to back off after a **429**
- **`supports_credentials`**: `false`. Authentication is a bearer token, not a cookie, so
  credentialed requests are unnecessary; keeping this off is also what makes any wildcard
  configuration safe. **Never** combine `supports_credentials: true` with `allowed_origins: ['*']`.
- **`max_age`**: `86400`, so browsers cache the preflight for a day rather than doubling
  request volume on shared hosting.

Note: when exactly one origin is configured and no patterns are set, the underlying CORS
library emits that origin statically rather than per request. The browser still refuses a
mismatched origin. Configure two or more origins if you want the header resolved per request.

**Secrets**: no API key, database credential, or mail credential is ever returned by any
endpoint. Payment proof file paths are never serialised (see §7).

---

## 2. Response envelope

### Success — single resource

```json
{
  "success": true,
  "data": { "...": "..." },
  "message": "Optional human-readable confirmation."
}
```

### Success — paginated collection

```json
{
  "success": true,
  "data": [],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 68 },
  "links": {
    "first": "https://api.example.com/api/v1/staycations?page=1",
    "last":  "https://api.example.com/api/v1/staycations?page=5",
    "prev":  null,
    "next":  "https://api.example.com/api/v1/staycations?page=2"
  }
}
```

Some endpoints add extra keys to `meta` (documented per endpoint).

### Failure

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": { "end_date": ["The check-out date must be after the check-in date."] }
}
```

```json
{
  "success": false,
  "message": "The selected dates are not available for Seaside Villa.",
  "error_code": "dates_unavailable",
  "conflicts": [
    { "type": "booking", "start_date": "2026-10-11", "end_date": "2026-10-14", "reason": null }
  ]
}
```

`errors` appears only on 422 validation failures. `error_code` appears on domain-rule
failures and is the stable value to branch on.

### Status codes and error codes

| Status | `error_code` | Meaning |
|---|---|---|
| 200 | — | Success |
| 201 | — | Resource created |
| 401 | `unauthenticated` | Missing, malformed, or revoked token |
| 403 | `forbidden` | Authenticated but not permitted |
| 404 | `not_found` | No such resource |
| 409 | `dates_unavailable` | The range collides with a booking or blocked date |
| 422 | — | Validation failure (`errors` present) |
| 422 | `invalid_dates` | Dates parse but violate a stay rule (past, too long, too short) |
| 422 | `guest_capacity_exceeded` | Party size outside the allowed range |
| 422 | `staycation_not_bookable` | The listing is marked unavailable |
| 422 | `booking_not_cancellable` | The booking's status no longer permits cancellation |
| 422 | `invalid_payment_transition` | The requested payment status change is not allowed |
| 429 | `too_many_attempts` | Login throttled; `retry_after` gives seconds |
| 500 | `server_error` | Unexpected failure (details only when `APP_DEBUG=true`) |

---

## 3. Money, dates and capacity

**The server is authoritative for every monetary value.** No endpoint accepts a price,
total, or amount from the client; any such field in a request body is ignored. Amounts are
computed with `bcmath` and returned as **decimal strings** with two places (`"9000.00"`),
never as floats.

**Dates** are `YYYY-MM-DD`. A stay occupies the half-open interval
`[start_date, end_date)` — the guest sleeps every night from check-in up to but not
including check-out. Consequently a stay ending on the 15th and a stay starting on the
15th **do not** conflict.

Pricing rules (configurable in `config/booking.php`):

| Rule | Default | Env override |
|---|---|---|
| Max guests | 8 | `BOOKING_MAX_GUESTS` |
| Guests included in the base rate | 6 | `BOOKING_FREE_GUEST_THRESHOLD` |
| Fee per extra guest (whole stay) | 500 | `BOOKING_EXTRA_GUEST_FEE` |
| Minimum nights | 1 | `BOOKING_MIN_NIGHTS` |
| Maximum nights | 30 | `BOOKING_MAX_NIGHTS` |
| Max days booked in advance | 365 | `BOOKING_MAX_ADVANCE_DAYS` |
| Deposit fraction for `payment_type: half` | 0.5 | `BOOKING_DEPOSIT_RATIO` |

`total_price = (price_per_night × nights) + (max(0, guests − 6) × 500)`

The deposit is rounded to the cent such that `deposit_amount + balance_due == total_price`
exactly.

---

## 4. Booking statuses

`status` governs whether a booking reserves the calendar.

| `status` | Blocks availability | Meaning |
|---|---|---|
| `waiting` | **yes** | Legacy default from the original schema |
| `pending` | **yes** | Submitted, awaiting admin review |
| `approved` | **yes** | Approved, awaiting payment verification |
| `confirmed` | **yes** | Payment verified, stay locked in |
| `completed` | **yes** | Stay has taken place |
| `declined` | no | Rejected by an admin — dates released |
| `cancelled` | no | Withdrawn by the customer or voided by an admin — dates released |

`payment_status`: `unpaid`, `pending`, `half_paid`, `paid`, `failed`, `refunded`.
Admins may set only `unpaid`, `pending`, `half_paid`, `paid`, `failed`.

A customer may cancel their own booking while it is `waiting`, `pending` or `approved`.

---

## 5. Public endpoints

### `GET /api/v1/staycations`

Paginated catalogue. No authentication.

| Query | Type | Notes |
|---|---|---|
| `page` | int | Default 1 |
| `per_page` | int | Default 15, capped at 50 |
| `available_only` | bool | Only listings marked available |
| `search` | string | Matches name or location |

**200**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Seaside Villa",
      "description": "A lovely place.",
      "location": "Batangas",
      "price_per_night": "3000.00",
      "currency": "PHP",
      "availability": "available",
      "is_bookable": true,
      "image_url": "https://.../house.jpg",
      "max_guests": 8,
      "rating": { "average": 4.6, "count": 12 },
      "created_at": "2026-01-04T02:11:00+00:00"
    }
  ],
  "meta": { "current_page": 1, "last_page": 2, "per_page": 15, "total": 18 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}
```

### `GET /api/v1/staycations/{staycation}`

Single listing, with `images` and `rating`. **200** / **404** `not_found`.

### `GET /api/v1/staycations/{staycation}/availability`

| Query | Required | Format |
|---|---|---|
| `start_date` | yes | `YYYY-MM-DD` |
| `end_date` | yes | `YYYY-MM-DD`, after `start_date` |

**200**

```json
{
  "success": true,
  "data": {
    "staycation_id": 1,
    "start_date": "2026-10-10",
    "end_date": "2026-10-13",
    "nights": 3,
    "is_available": false,
    "is_bookable": true,
    "conflicts": [
      { "type": "booking",      "start_date": "2026-10-11", "end_date": "2026-10-12", "reason": null },
      { "type": "blocked_date", "start_date": "2026-10-12", "end_date": "2026-10-13", "reason": "Maintenance" }
    ]
  }
}
```

`conflicts` is empty when the range is free. **422** on malformed dates.

### `POST /api/v1/staycations/{staycation}/quote`

The authoritative price for a stay. Persists nothing.

```json
{ "start_date": "2026-10-10", "end_date": "2026-10-13", "guest_number": 4 }
```

**200**

```json
{
  "success": true,
  "data": {
    "staycation_id": 1,
    "is_available": true,
    "conflicts": [],
    "quote": {
      "start_date": "2026-10-10",
      "end_date": "2026-10-13",
      "nights": 3,
      "guest_number": 4,
      "price_per_night": "3000.00",
      "accommodation_total": "9000.00",
      "extra_guests": 0,
      "extra_guest_fee": "0.00",
      "total_price": "9000.00",
      "deposit_amount": "4500.00",
      "balance_due": "4500.00",
      "currency": "PHP"
    }
  }
}
```

A quote is still returned when `is_available` is `false`, so the UI can show the price
alongside the reason the dates cannot be taken.

**422** `guest_capacity_exceeded` / `invalid_dates`, or a validation error.

---

## 6. Authentication endpoints

### `POST /api/v1/auth/register`

Throttled to 10 requests/minute per IP.

```json
{
  "name": "Ana Reyes",
  "email": "ana@example.com",
  "password": "correct-horse-battery",
  "password_confirmation": "correct-horse-battery",
  "device_name": "web"
}
```

**201**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 7, "name": "Ana Reyes", "email": "ana@example.com",
      "role": "user", "email_verified": false,
      "profile_photo_url": "https://.../default.png",
      "created_at": "2026-08-29T09:00:00+00:00"
    },
    "token": "7|xxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  },
  "message": "Account created."
}
```

**422** on a duplicate email or a weak/unconfirmed password.

### `POST /api/v1/auth/login`

Throttled to 10 requests/minute per IP, **and** locked out after 5 failed attempts per
email+IP for 60 seconds.

```json
{ "email": "ana@example.com", "password": "correct-horse-battery", "device_name": "web" }
```

**200** — same shape as register. **422** with a generic
`"These credentials do not match our records."` on `email` for both an unknown address and
a wrong password, so the endpoint cannot be used to enumerate accounts.
**429** `too_many_attempts` with `retry_after` (seconds) once locked out.

### `POST /api/v1/auth/logout`

Requires auth. Revokes the calling token only. **200**.

### `POST /api/v1/auth/logout-all`

Requires auth. Revokes every token for the account. **200**.

### `GET /api/v1/me`

Requires auth. **200** with the user object above. **401** `unauthenticated` otherwise.

---

## 6a. Images and files

**Public staycation images** are returned as **absolute URLs** built from `APP_URL`, so the
client can use them directly in `<img src>` without knowing anything about Laravel's storage
layout:

```json
"image_url": "https://api.example.com/storage/staycations/1700000000.jpg",
"images": [
  { "id": 4, "url": "https://api.example.com/storage/staycations/gallery/g1.jpg" }
]
```

- The database stores only the disk-relative path (`staycations/<file>`); the `/storage/`
  prefix and host are applied by `App\Support\MediaUrl`.
- A row that already carries the `storage/` prefix, or a full `http(s)://` URL, is returned
  unchanged — so legacy rows and any future CDN both work.
- A missing image is `null`, never a broken URL.
- **`APP_URL` must be set to the real backend origin in production**, otherwise these URLs
  point at `localhost`.

**Payment proofs are private** and are the deliberate exception: they live on a private disk,
their stored path is never serialised into any response body, and there is no public URL for
them. They are streamed only through `GET /api/v1/bookings/{booking}/proof` after an
authorization check. See §7.

---

## 7. Customer endpoints

All require `Authorization: Bearer <token>`.

### `GET /api/v1/profile` · `PUT /api/v1/profile`

`PUT` body: `{ "name": "...", "email": "..." }`. Changing the email sets
`email_verified` back to `false`. **200** / **422**.

### `PUT /api/v1/profile/password`

```json
{
  "current_password": "old-password",
  "password": "new-password",
  "password_confirmation": "new-password"
}
```

**200**, and every *other* token is revoked. **422** if `current_password` is wrong or the
new password matches the old one.

### `GET /api/v1/bookings`

The caller's own bookings only, newest stay first.

| Query | Notes |
|---|---|
| `page`, `per_page` | `per_page` default 15, capped at 50 |
| `status` | Filter by booking status |

**200**, paginated, each item shaped as below.

### `GET /api/v1/bookings/{booking}`

**200**

```json
{
  "success": true,
  "data": {
    "id": 41,
    "reference": "BK-000041",
    "status": "pending",
    "blocks_availability": true,
    "guest": { "name": "Ana Reyes", "email": "ana@example.com", "phone": "09171234567", "guest_number": 4 },
    "stay": { "start_date": "2026-10-10", "end_date": "2026-10-13", "nights": 3 },
    "pricing": {
      "price_per_night": "3000.00",
      "total_price": "9000.00",
      "amount_paid": "0.00",
      "balance_due": "9000.00",
      "currency": "PHP"
    },
    "payment": {
      "status": "pending",
      "method": "gcash",
      "transaction_number": "TXN-12345",
      "proof_url": "https://.../api/v1/bookings/41/proof"
    },
    "message_to_admin": null,
    "staycation": { "...": "..." },
    "payments": [
      { "id": 9, "amount": "4500.00", "type": "deposit", "status": "pending",
        "payment_method": "gcash", "reference_number": "TXN-12345",
        "has_proof": true, "verified_at": null, "created_at": "..." }
    ],
    "can": { "cancel": true },
    "created_at": "...", "updated_at": "..."
  }
}
```

**403** `forbidden` for someone else's booking.

### `POST /api/v1/bookings`

`multipart/form-data` (a file is required).

| Field | Type | Rules |
|---|---|---|
| `staycation_id` | int | Must exist |
| `start_date` | date | `YYYY-MM-DD` |
| `end_date` | date | `YYYY-MM-DD`, after `start_date` |
| `guest_number` | int | 1 … `max_guests` |
| `phone` | string | ≤ 20 chars |
| `payment_type` | enum | `half` \| `full` |
| `payment_method` | enum | `gcash` \| `bpi` |
| `payment_proof` | file | JPEG/PNG/WebP, ≤ 5 MB |
| `transaction_number` | string? | ≤ 255 |
| `message_to_admin` | string? | ≤ 500 |

**No price fields are accepted.** The server recomputes `total_price`, `price_per_day` and
the deposit from `config/booking.php` and the staycation's current rate.

**201** with the booking object. The booking is created as `status: pending`,
`payment_status: pending`, `amount_paid: "0.00"`, and a **pending** ledger entry is written
for the amount the customer claims to have sent. Nothing is credited until an admin
verifies it.

Failures: **409** `dates_unavailable` (with `conflicts`), **422**
`staycation_not_bookable` / `invalid_dates` / `guest_capacity_exceeded`, **422** validation,
**401** `unauthenticated`.

> **Concurrency.** Availability is re-checked *inside* a transaction that holds a
> `SELECT ... FOR UPDATE` lock on the staycation row, so two simultaneous submissions for
> the same listing are serialised and the second is rejected with `dates_unavailable`.
> Requests for different staycations do not contend. This needs no Redis, queue or worker
> and is safe on Hostinger shared hosting.

### `DELETE /api/v1/bookings/{booking}`

Customer cancellation. The row is **kept** (so the customer keeps their history) and moved
to `status: cancelled`, which releases the dates. Also archived to `booking_history` for
the admin cancelled-bookings screen.

**200** with the updated booking. **403** `forbidden` if the booking is not the caller's or
its status no longer permits self-cancellation (e.g. `confirmed`).

### `GET /api/v1/bookings/{booking}/proof`

Streams the payment proof image. **This is the only way a proof leaves the server.**

Proofs are written to `storage/app/private/payment_proofs/<uuid>.<ext>` — outside the web
root, under an unguessable name — and are served only to the booking's owner or a
staff/admin account. The stored path is never serialised in any response.

**200** with `Content-Type: image/*` and `Cache-Control: private, no-store`.
**403** `forbidden`, **404** `proof_not_found`.

---

## 8. Back-office endpoints

Prefix: `/api/v1/admin`. All require authentication.

- **Reading** (`GET`) requires `admin` **or** `staff`.
- **Every mutation** requires `admin`. Staff receive **403** `forbidden`.

### `GET /api/v1/admin/dashboard`

```json
{
  "success": true,
  "data": {
    "bookings": { "total": 84, "pending": 5, "approved": 3, "confirmed": 61,
                  "cancelled": 9, "declined": 6, "arriving_today": 2, "in_house": 4 },
    "payments": { "awaiting_verification": 5, "unpaid_bookings": 8, "half_paid_bookings": 12,
                  "collected_total": "412500.00", "expected_total": "530000.00",
                  "outstanding_total": "117500.00" },
    "catalogue": { "staycations": 6, "available": 5 },
    "customers": { "total": 143, "staff": 2 },
    "generated_at": "2026-08-29T09:00:00+00:00"
  }
}
```

### `GET /api/v1/admin/bookings`

Filters: `status`, `payment_status`, `staycation_id`, `from`, `to`,
`search` (name / email / transaction number), `page`, `per_page` (default 20, cap 100).

### `GET /api/v1/admin/bookings/{booking}`

Full booking including `payments[].verified_by`.

### `POST /api/v1/admin/bookings/{booking}/approve` — admin only

Re-checks availability first (excluding this booking). A range taken since submission is
rejected with **409** `dates_unavailable` rather than double-booked. On success sets
`status: approved`, `payment_status: pending`, and writes an audit log entry. **200**.

### `POST /api/v1/admin/bookings/{booking}/decline` — admin only

Body: `{ "reason": "optional, ≤ 500 chars" }`. Sets `status: declined`,
`payment_status: failed`. **The dates are released immediately.** **200**.

### `POST /api/v1/admin/bookings/{booking}/cancel` — admin only

Body: `{ "reason": "optional" }`. Voids from any status and releases the dates. **200**.

### `PUT /api/v1/admin/bookings/{booking}/payment-status` — admin only

```json
{ "payment_status": "half_paid" }
```

Accepts only `unpaid`, `pending`, `half_paid`, `paid`, `failed` — anything else is **422**
with a validation error on `payment_status`.

`amount_paid` is kept consistent with the status, and the ledger is reconciled (superseded
rows are marked `rejected`, never deleted):

| `payment_status` | `amount_paid` becomes | Booking `status` becomes |
|---|---|---|
| `paid` | `total_price` | `confirmed` |
| `half_paid` | deposit (half the total) | `confirmed` |
| `unpaid` | `0.00` | `cancelled` — this is how staff void a booking |
| `failed` | `0.00` | `declined` |
| `pending` | `0.00` | `approved` |

### `POST /api/v1/admin/bookings/{booking}/mark-fully-paid` — admin only

Settles the balance on a **half-paid** booking. Writes a verified `balance` ledger entry for
the outstanding amount and sets `amount_paid` to the booking total.

> Previously this flipped `payment_status` to `paid` while leaving `amount_paid` at the
> deposit, so receipts and reports understated what had been collected. That is fixed.

**200**. **422** `invalid_payment_transition` if the booking is not currently `half_paid`.

### `POST /api/v1/admin/bookings/{booking}/payments` — admin only

Records money confirmed outside the normal flow, as a **verified** ledger entry.

```json
{ "amount": "4500.00", "type": "balance", "payment_method": "bpi",
  "reference_number": "REF-9", "notes": "Paid in cash on arrival." }
```

`type`: `deposit` | `balance` | `full` | `refund`. **201**.

### `GET /api/v1/admin/payments` · `GET /api/v1/admin/payments/{payment}`

Filters: `status`, `type`, `booking_id`, `awaiting_verification`, `page`, `per_page`.
`meta` additionally carries `pending_count` and `verified_total`.

### `POST /api/v1/admin/payments/{payment}/verify` — admin only

Marks the ledger entry verified and recomputes `amount_paid` and `payment_status` on the
booking from the sum of **verified** entries only. **200**.

### `POST /api/v1/admin/payments/{payment}/reject` — admin only

Body: `{ "notes": "optional, ≤ 500" }`. Marks the entry rejected and recomputes the
booking's totals, which excludes it. **200**.

### `GET /api/v1/admin/staycations` · `GET /api/v1/admin/staycations/{staycation}`

Listing carries `bookings_count`, `blocked_dates_count` and rating aggregates.

### `POST /api/v1/admin/staycations` — admin only

```json
{ "house_name": "Seaside Villa", "house_description": "...",
  "house_price": 4200, "house_location": "Batangas",
  "house_availability": "available" }
```

**201**.

### `PUT /api/v1/admin/staycations/{staycation}` — admin only

Same fields, all optional (`sometimes`). **200**.

### `POST /api/v1/admin/staycations/{staycation}/toggle-availability` — admin only

Flips between `available` and `unavailable`. Existing bookings are untouched — taking a
listing off sale never voids confirmed stays. **200**.

### `GET /api/v1/admin/customers` · `GET /api/v1/admin/customers/{customer}`

Listing supports `search` and carries `bookings_count`. The detail response returns
`customer`, their full `bookings`, and `stats` (`total_bookings`, `lifetime_value`).

### `GET /api/v1/admin/blocked-dates`

Filter by `staycation_id`.

### `POST /api/v1/admin/blocked-dates` — admin only

```json
{ "staycation_id": 1, "start_date": "2026-11-01", "end_date": "2026-11-05",
  "reason": "Maintenance" }
```

Uses the **same half-open semantics as bookings**: this blocks the nights of the 1st
through the 4th, and the 5th remains bookable. **201**.

### `DELETE /api/v1/admin/blocked-dates/{blockedDate}` — admin only

**200**.

### `GET /api/v1/admin/reviews`

Filters: `staycation_id`, `rating`. `meta` adds `average_rating`.

### `DELETE /api/v1/admin/reviews/{review}` — admin only

**200**.

---

## 9. Rate limiting

| Scope | Limit |
|---|---|
| `POST /auth/register`, `POST /auth/login` | 10 requests / minute / IP |
| Failed logins | 5 per email+IP, then a 60-second lockout (**429** `too_many_attempts`) |
| All other API routes | Laravel's default `api` throttle (60 / minute) |

---

## 10. Not yet implemented

These are deliberate gaps, not oversights:

- **Password reset over the API.** The Blade application still owns the reset flow
  (`/reset-password`). A tokenised JSON equivalent is not exposed yet.
- **Email verification over the API.** `email_verified` is reported but there is no API
  endpoint to trigger or confirm verification.
- **Review submission over the API.** Reviews can be read and deleted by the back office;
  customers still post reviews through the Blade form.
- **Staff-scoped write endpoints.** Staff have read-only API access; all mutations are
  admin-only.
- **Refund processing.** The ledger models a `refund` payment type, but no endpoint issues
  one yet.

---

## 11. Frontend integration summary

```
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1     # local
NEXT_PUBLIC_API_URL=https://<hostinger-domain>/api/v1 # production
```

Every request:

```
Accept: application/json
Content-Type: application/json          # omit for multipart uploads; the browser sets it
Authorization: Bearer <token>           # authenticated requests only
```

Backend environment variables the deployment must set:

| Variable | Why |
|---|---|
| `APP_URL` | Builds absolute image URLs (§6a) and pagination links |
| `APP_DEBUG=false` | Keeps error details out of production responses |
| `FRONTEND_URL` | The CORS allow list (§1) |
| `CORS_ALLOWED_ORIGINS` / `CORS_ALLOWED_ORIGIN_PATTERNS` | Additional or preview origins |
| `DB_*` | MySQL connection |
| `BOOKING_PROOF_DISK` | Keeps payment proofs off the public disk |
| `BOOKING_*` | Pricing and stay rules (§3) |

Log in, store the token, send it as a bearer header, and branch on the `success` flag plus
the HTTP status. Nothing else about the backend needs to be known by the client.
