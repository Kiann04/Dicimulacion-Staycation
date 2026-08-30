import test from 'node:test';
import assert from 'node:assert/strict';

import {
  mapApiStaycationToSummary,
  mapApiStaycationToDetails,
  mapApiAvailabilityToResult,
  mapApiStaycationCollectionToResult,
} from '../adapters.ts';
import {
  isValidStaycationId,
  LaravelStaycationService,
} from '../laravel-staycation-service.ts';
import {
  ApiClientError,
  getApiBaseUrl,
  apiFetch,
} from '../http-client.ts';
import { getBookingFormIds } from '../../utils/form-ids.ts';

// Sample authoritative Laravel ApiStaycation DTO
const sampleApiStaycation = {
  id: 1,
  name: 'Seaside Retreat',
  description: 'Two-storey house facing the cove...',
  location: 'Cebu City',
  currency: 'PHP',
  price_per_night: '4500.00',
  price_per_night_centavos: 450000,
  availability_status: 'available',
  is_bookable: true,
  capacity: {
    included_guests: 6,
    maximum_guests: 8,
    extra_guest_fee: '500.00',
    extra_guest_fee_centavos: 50000,
  },
  rating: { average: 4.5, count: 12 },
  image_url: 'https://api.example.com/storage/staycations/main.jpg',
  gallery: ['https://api.example.com/storage/staycations/gallery/one.jpg'],
};

const sampleApiCollection = {
  data: [sampleApiStaycation],
  links: {
    first: 'http://localhost:8000/api/v1/staycations?page=1',
    last: 'http://localhost:8000/api/v1/staycations?page=3',
    prev: null,
    next: 'http://localhost:8000/api/v1/staycations?page=2',
  },
  meta: {
    current_page: 1,
    from: 1,
    last_page: 3,
    path: 'http://localhost:8000/api/v1/staycations',
    per_page: 15,
    to: 15,
    total: 42,
  },
};

test('1. Money: availability mapping does NOT calculate or derive a stay total', () => {
  const availabilityData = {
    staycation_id: 1,
    start_date: '2026-09-10',
    end_date: '2026-09-14',
    nights: 4,
    available: true,
    unavailable_reasons: [],
    reserves_inventory: false,
  };

  const result = mapApiAvailabilityToResult(
    availabilityData,
    {
      price_per_night: '4500.00',
      price_per_night_centavos: 450000,
      currency: 'PHP',
    },
    2
  );

  assert.equal(result.isAvailable, true);
  assert.equal(result.nights, 4);
  assert.equal(result.pricePerNight, '4500.00');
  assert.equal(result.pricePerNightCentavos, 450000);
  assert.equal(result.priceBreakdown, undefined);
  assert.equal(result.total, undefined);
  assert.equal(result.estimatedTotal, undefined);
  assert.match(result.notice, /Final pricing and fees will be confirmed during reservation/);
});

test('2. ID Validation: raw strings with whitespace or invalid formats are strictly rejected', () => {
  // Valid raw strings
  assert.equal(isValidStaycationId('1'), true);
  assert.equal(isValidStaycationId('42'), true);
  assert.equal(isValidStaycationId(1), true);

  // Invalid raw strings (must NOT trim before checking)
  assert.equal(isValidStaycationId(' 1 '), false);
  assert.equal(isValidStaycationId('\t42\n'), false);
  assert.equal(isValidStaycationId('1abc'), false);
  assert.equal(isValidStaycationId('1.5'), false);
  assert.equal(isValidStaycationId('0'), false);
  assert.equal(isValidStaycationId('-1'), false);
  assert.equal(isValidStaycationId(''), false);
  assert.equal(isValidStaycationId(null), false);
  assert.equal(isValidStaycationId(undefined), false);
});

test('3. Service Level: collection endpoint returning {"wrong": true} is rejected by LaravelStaycationService', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () =>
    new Response(JSON.stringify({ wrong: true }), {
      status: 200,
      headers: { 'content-type': 'application/json' },
    });

  try {
    const service = new LaravelStaycationService();
    await assert.rejects(
      async () => service.getStaycationCollection(),
      (err) => err instanceof ApiClientError && err.status === 502
    );
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('4. Service Level: collection endpoint with null/non-string links.first or links.last is rejected', async () => {
  const originalFetch = globalThis.fetch;

  // Test 1: links.first is null
  globalThis.fetch = async () =>
    new Response(
      JSON.stringify({
        data: [sampleApiStaycation],
        links: { first: null, last: 'http://localhost:8000/api/v1/staycations?page=1', prev: null, next: null },
        meta: sampleApiCollection.meta,
      }),
      {
        status: 200,
        headers: { 'content-type': 'application/json' },
      }
    );

  try {
    const service = new LaravelStaycationService();
    await assert.rejects(
      async () => service.getStaycationCollection(),
      (err) => err instanceof ApiClientError && err.status === 502
    );
  } finally {
    globalThis.fetch = originalFetch;
  }

  // Test 2: links.first is a number
  globalThis.fetch = async () =>
    new Response(
      JSON.stringify({
        data: [sampleApiStaycation],
        links: { first: 123, last: 'http://localhost:8000/api/v1/staycations?page=1', prev: null, next: null },
        meta: sampleApiCollection.meta,
      }),
      {
        status: 200,
        headers: { 'content-type': 'application/json' },
      }
    );

  try {
    const service = new LaravelStaycationService();
    await assert.rejects(
      async () => service.getStaycationCollection(),
      (err) => err instanceof ApiClientError && err.status === 502
    );
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('5. Service Level: valid collection links (first/last strings, prev/next nullable) succeeds', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () =>
    new Response(JSON.stringify(sampleApiCollection), {
      status: 200,
      headers: { 'content-type': 'application/json' },
    });

  try {
    const service = new LaravelStaycationService();
    const result = await service.getStaycationCollection();
    assert.equal(result.items.length, 1);
    assert.equal(result.meta?.total, 42);
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('6. Service Level: collection endpoint containing malformed staycation item is rejected', async () => {
  const originalFetch = globalThis.fetch;
  const malformedItem = {
    ...sampleApiStaycation,
    capacity: {
      included_guests: 'six', // Invalid: string instead of number
      maximum_guests: 8,
      extra_guest_fee: '500.00',
      extra_guest_fee_centavos: 50000,
    },
  };

  globalThis.fetch = async () =>
    new Response(
      JSON.stringify({
        data: [malformedItem],
        links: sampleApiCollection.links,
        meta: sampleApiCollection.meta,
      }),
      {
        status: 200,
        headers: { 'content-type': 'application/json' },
      }
    );

  try {
    const service = new LaravelStaycationService();
    await assert.rejects(
      async () => service.getStaycationCollection(),
      (err) => err instanceof ApiClientError && err.status === 502
    );
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('7. Service Level: detail endpoint returning malformed 200 payload is rejected by getStaycationById', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () =>
    new Response(
      JSON.stringify({
        data: {
          id: 1,
          name: 'Missing other critical fields',
        },
      }),
      {
        status: 200,
        headers: { 'content-type': 'application/json' },
      }
    );

  try {
    const service = new LaravelStaycationService();
    await assert.rejects(
      async () => service.getStaycationById('1'),
      (err) => err instanceof ApiClientError && err.status === 502
    );
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('8. Service Level: availability endpoint returning unsupported reason ["made_up_reason"] is rejected', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async (url) => {
    const urlStr = String(url);
    if (urlStr.includes('/availability')) {
      return new Response(
        JSON.stringify({
          data: {
            staycation_id: 1,
            start_date: '2026-09-10',
            end_date: '2026-09-14',
            nights: 4,
            available: false,
            unavailable_reasons: ['made_up_reason'], // Unsupported reason code
            reserves_inventory: false,
          },
        }),
        {
          status: 200,
          headers: { 'content-type': 'application/json' },
        }
      );
    }
    return new Response(JSON.stringify({ data: sampleApiStaycation }), {
      status: 200,
      headers: { 'content-type': 'application/json' },
    });
  };

  try {
    const service = new LaravelStaycationService();
    const result = await service.checkAvailability(1, '2026-09-10', '2026-09-14', 2);
    assert.equal(result.status, 'error');
    assert.match(result.message, /unsupported unavailable reason/i);
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('9. Service Level: availability endpoint returning valid reasons ["booking_conflict"] is accepted', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async (url) => {
    const urlStr = String(url);
    if (urlStr.includes('/availability')) {
      return new Response(
        JSON.stringify({
          data: {
            staycation_id: 1,
            start_date: '2026-09-10',
            end_date: '2026-09-14',
            nights: 4,
            available: false,
            unavailable_reasons: ['booking_conflict'],
            reserves_inventory: false,
          },
        }),
        {
          status: 200,
          headers: { 'content-type': 'application/json' },
        }
      );
    }
    return new Response(JSON.stringify({ data: sampleApiStaycation }), {
      status: 200,
      headers: { 'content-type': 'application/json' },
    });
  };

  try {
    const service = new LaravelStaycationService();
    const result = await service.checkAvailability(1, '2026-09-10', '2026-09-14', 2);
    assert.equal(result.isAvailable, false);
    assert.equal(result.status, 'conflict');
    assert.match(result.message, /conflict with an existing reservation/);
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('10. HTTP 404 behavior: real service returns null for 404 response', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () =>
    new Response(JSON.stringify({ message: 'Staycation not found.' }), {
      status: 404,
      headers: { 'content-type': 'application/json' },
    });

  try {
    const service = new LaravelStaycationService();
    const result = await service.getStaycationById('99999');
    assert.equal(result, null);
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('11. HTTP 422 behavior: real apiFetch path captures validation error dictionary', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () =>
    new Response(
      JSON.stringify({
        message: 'The start date field is required.',
        errors: {
          start_date: ['The start date field is required.'],
          end_date: ['The departure date must be after the arrival date.'],
        },
      }),
      {
        status: 422,
        headers: { 'content-type': 'application/json' },
      }
    );

  try {
    await assert.rejects(
      async () => apiFetch('/api/v1/staycations/1/availability'),
      (err) => {
        assert(err instanceof ApiClientError);
        assert.equal(err.status, 422);
        assert.equal(err.isValidationError, true);
        assert.deepEqual(err.errors?.start_date, ['The start date field is required.']);
        return true;
      }
    );
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('12. HTTP 429 behavior: real apiFetch path returns customer-friendly message', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () =>
    new Response(JSON.stringify({ message: 'Too Many Attempts.' }), {
      status: 429,
      headers: { 'content-type': 'application/json' },
    });

  try {
    await assert.rejects(
      async () => apiFetch('/api/v1/staycations'),
      (err) => {
        assert(err instanceof ApiClientError);
        assert.equal(err.status, 429);
        assert.equal(err.isRateLimited, true);
        assert.equal(err.message, 'Too many requests. Please wait a moment and try again.');
        return true;
      }
    );
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('13. HTTP 500 behavior: real apiFetch path returns sanitized server error', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () =>
    new Response(JSON.stringify({ message: 'Server Error' }), {
      status: 500,
      headers: { 'content-type': 'application/json' },
    });

  try {
    await assert.rejects(
      async () => apiFetch('/api/v1/staycations'),
      (err) => {
        assert(err instanceof ApiClientError);
        assert.equal(err.status, 500);
        assert.equal(err.isServerError, true);
        assert.equal(err.message, 'A server error occurred. Please try again later.');
        return true;
      }
    );
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('14. Network failure behavior: fetch rejection message is sanitized', async () => {
  const originalFetch = globalThis.fetch;
  globalThis.fetch = async () => {
    throw new TypeError('fetch failed: ECONNREFUSED 127.0.0.1:8000');
  };

  try {
    await assert.rejects(
      async () => apiFetch('/api/v1/staycations'),
      (err) => {
        assert(err instanceof ApiClientError);
        assert.equal(err.status, 503);
        assert.doesNotMatch(err.message, /fetch failed/i);
        assert.doesNotMatch(err.message, /ECONNREFUSED/i);
        assert.equal(err.message, 'Unable to connect to the staycation service. Please verify the API is reachable.');
        return true;
      }
    );
  } finally {
    globalThis.fetch = originalFetch;
  }
});

test('15. Pagination: meta.total is preserved and exposed in collection result', () => {
  const result = mapApiStaycationCollectionToResult(sampleApiCollection);

  assert.equal(result.items.length, 1);
  assert.equal(result.meta?.total, 42);
  assert.equal(result.meta?.currentPage, 1);
  assert.equal(result.meta?.lastPage, 3);
  assert.equal(result.meta?.perPage, 15);
});

test('16. Collection semantics: uncontracted property type is absent and not fabricated', () => {
  const summary = mapApiStaycationToSummary(sampleApiStaycation);
  const details = mapApiStaycationToDetails(sampleApiStaycation);

  assert.equal(summary.propertyType, undefined);
  assert.equal(details.propertyType, undefined);
});

test('17. Mobile & Desktop Form ID isolation: unique and deterministically mapped', () => {
  const mobileIds = getBookingFormIds('mobile-');
  const desktopIds = getBookingFormIds('desktop-');

  assert.equal(mobileIds.widgetId, 'mobile-booking-widget');
  assert.equal(mobileIds.checkInId, 'mobile-check-in-date');
  assert.equal(mobileIds.checkOutId, 'mobile-check-out-date');
  assert.equal(mobileIds.guestsId, 'mobile-guests-count');

  assert.equal(desktopIds.widgetId, 'desktop-booking-widget');
  assert.equal(desktopIds.checkInId, 'desktop-check-in-date');
  assert.equal(desktopIds.checkOutId, 'desktop-check-out-date');
  assert.equal(desktopIds.guestsId, 'desktop-guests-count');

  // Verify zero collisions
  assert.notEqual(mobileIds.widgetId, desktopIds.widgetId);
  assert.notEqual(mobileIds.checkInId, desktopIds.checkInId);
});

test('18. Production config behavior: missing base URL in production fails', () => {
  const prevEnv = process.env.NODE_ENV;
  const prevUrl = process.env.NEXT_PUBLIC_API_BASE_URL;

  try {
    process.env.NODE_ENV = 'production';
    delete process.env.NEXT_PUBLIC_API_BASE_URL;

    assert.throws(
      () => getApiBaseUrl(),
      (err) => err instanceof ApiClientError && err.status === 500
    );
  } finally {
    process.env.NODE_ENV = prevEnv;
    process.env.NEXT_PUBLIC_API_BASE_URL = prevUrl;
  }
});
