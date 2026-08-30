import { ApiClientError } from './http-client.ts';

/**
 * Authoritative API DTO definitions matching docs/api-contract.md (v1 Phase 2A).
 * 
 * These types represent the exact JSON response shapes from the Laravel REST API.
 * UI components must NOT consume these directly; use adapters in lib/api/adapters.ts
 * to map them into domain models.
 */

export interface ApiCapacity {
  included_guests: number;
  maximum_guests: number;
  extra_guest_fee: string; // e.g. "500.00"
  extra_guest_fee_centavos: number; // e.g. 50000
}

export interface ApiRating {
  average: number | null; // e.g. 4.5 or null if no reviews
  count: number; // e.g. 12 or 0
}

export interface ApiStaycation {
  id: number;
  name: string;
  description: string;
  location: string; // e.g. "Cebu City"
  currency: string; // "PHP"
  price_per_night: string; // exact decimal string e.g. "4500.00"
  price_per_night_centavos: number; // integer centavos e.g. 450000
  availability_status: 'available' | 'unavailable' | string;
  is_bookable: boolean;
  capacity: ApiCapacity;
  rating: ApiRating;
  image_url: string | null;
  gallery: string[]; // absolute URLs
}

export interface ApiPaginationLinks {
  first: string;
  last: string;
  prev: string | null;
  next: string | null;
}

export interface ApiPaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  path: string;
  per_page: number;
  to: number | null;
  total: number;
}

export interface ApiStaycationCollectionResponse {
  data: ApiStaycation[];
  links: ApiPaginationLinks;
  meta: ApiPaginationMeta;
}

export interface ApiStaycationSingleResponse {
  data: ApiStaycation;
}

export type ApiUnavailableReason =
  | 'property_unavailable'
  | 'booking_conflict'
  | 'blocked_dates';

export const VALID_UNAVAILABLE_REASONS = new Set<string>([
  'property_unavailable',
  'booking_conflict',
  'blocked_dates',
]);

export interface ApiAvailabilityData {
  staycation_id: number;
  start_date: string; // "YYYY-MM-DD"
  end_date: string; // "YYYY-MM-DD"
  nights: number;
  available: boolean;
  unavailable_reasons: ApiUnavailableReason[];
  reserves_inventory: false;
}

export interface ApiAvailabilityResponse {
  data: ApiAvailabilityData;
}

export interface ApiErrorResponse {
  message: string;
  errors?: Record<string, string[]>;
}

/**
 * Complete runtime validator for ApiStaycation resource.
 * Enforces contract-critical types and nullabilities from docs/api-contract.md.
 */
export function validateApiStaycation(item: unknown): ApiStaycation {
  if (typeof item !== 'object' || item === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: expected staycation object.');
  }

  const obj = item as Record<string, unknown>;

  if (
    typeof obj.id !== 'number' ||
    typeof obj.name !== 'string' ||
    typeof obj.description !== 'string' ||
    typeof obj.location !== 'string' ||
    typeof obj.currency !== 'string' ||
    typeof obj.price_per_night !== 'string' ||
    typeof obj.price_per_night_centavos !== 'number' ||
    typeof obj.availability_status !== 'string' ||
    typeof obj.is_bookable !== 'boolean'
  ) {
    throw new ApiClientError(502, 'Invalid API response envelope: missing or invalid required staycation fields.');
  }

  // Validate capacity object
  if (typeof obj.capacity !== 'object' || obj.capacity === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: missing staycation capacity object.');
  }
  const cap = obj.capacity as Record<string, unknown>;
  if (
    typeof cap.included_guests !== 'number' ||
    typeof cap.maximum_guests !== 'number' ||
    typeof cap.extra_guest_fee !== 'string' ||
    typeof cap.extra_guest_fee_centavos !== 'number'
  ) {
    throw new ApiClientError(502, 'Invalid API response envelope: malformed staycation capacity fields.');
  }

  // Validate rating object
  if (typeof obj.rating !== 'object' || obj.rating === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: missing staycation rating object.');
  }
  const rat = obj.rating as Record<string, unknown>;
  if (
    (rat.average !== null && typeof rat.average !== 'number') ||
    typeof rat.count !== 'number'
  ) {
    throw new ApiClientError(502, 'Invalid API response envelope: malformed staycation rating fields.');
  }

  // Validate image_url (string | null) and gallery (string[])
  if (obj.image_url !== null && typeof obj.image_url !== 'string') {
    throw new ApiClientError(502, 'Invalid API response envelope: image_url must be string or null.');
  }

  if (!Array.isArray(obj.gallery) || !obj.gallery.every((url) => typeof url === 'string')) {
    throw new ApiClientError(502, 'Invalid API response envelope: gallery must be an array of strings.');
  }

  return item as ApiStaycation;
}

/**
 * Complete runtime validator for collection envelope.
 * Strictly enforces first and last as URL strings per docs/api-contract.md.
 */
export function validateApiStaycationCollection(response: unknown): ApiStaycationCollectionResponse {
  if (typeof response !== 'object' || response === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: expected collection object.');
  }

  const obj = response as Record<string, unknown>;

  if (!Array.isArray(obj.data)) {
    throw new ApiClientError(502, 'Invalid API response envelope: expected collection data array.');
  }

  // Validate links object (first: string, last: string, prev: string | null, next: string | null)
  if (typeof obj.links !== 'object' || obj.links === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: missing pagination links.');
  }
  const links = obj.links as Record<string, unknown>;
  if (typeof links.first !== 'string' || typeof links.last !== 'string') {
    throw new ApiClientError(502, 'Invalid API response envelope: links.first and links.last must be URL strings.');
  }
  const isNullableString = (val: unknown) => val === null || typeof val === 'string';
  if (!isNullableString(links.prev) || !isNullableString(links.next)) {
    throw new ApiClientError(502, 'Invalid API response envelope: links.prev and links.next must be strings or null.');
  }

  // Validate meta object
  if (typeof obj.meta !== 'object' || obj.meta === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: missing pagination meta.');
  }
  const meta = obj.meta as Record<string, unknown>;
  if (
    typeof meta.current_page !== 'number' ||
    typeof meta.per_page !== 'number' ||
    typeof meta.total !== 'number' ||
    typeof meta.last_page !== 'number' ||
    (meta.from !== null && typeof meta.from !== 'number') ||
    (meta.to !== null && typeof meta.to !== 'number') ||
    typeof meta.path !== 'string'
  ) {
    throw new ApiClientError(502, 'Invalid API response envelope: malformed pagination meta fields.');
  }

  // Validate every item in data array
  obj.data.forEach(validateApiStaycation);

  return response as ApiStaycationCollectionResponse;
}

/**
 * Complete runtime validator for single resource envelope.
 */
export function validateApiStaycationSingle(response: unknown): ApiStaycationSingleResponse {
  if (typeof response !== 'object' || response === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: expected single resource object.');
  }

  const obj = response as Record<string, unknown>;
  if (!obj.data) {
    throw new ApiClientError(502, 'Invalid API response envelope: missing resource data.');
  }

  validateApiStaycation(obj.data);

  return response as ApiStaycationSingleResponse;
}

/**
 * Complete runtime validator for availability envelope.
 * Strictly enforces unavailable_reasons allowlist matching docs/api-contract.md.
 */
export function validateApiAvailability(response: unknown): ApiAvailabilityResponse {
  if (typeof response !== 'object' || response === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: expected availability object.');
  }

  const obj = response as Record<string, unknown>;
  if (typeof obj.data !== 'object' || obj.data === null) {
    throw new ApiClientError(502, 'Invalid API response envelope: missing availability data.');
  }

  const data = obj.data as Record<string, unknown>;
  if (
    typeof data.staycation_id !== 'number' ||
    typeof data.start_date !== 'string' ||
    typeof data.end_date !== 'string' ||
    typeof data.nights !== 'number' ||
    typeof data.available !== 'boolean' ||
    data.reserves_inventory !== false
  ) {
    throw new ApiClientError(502, 'Invalid API response envelope: malformed availability data.');
  }

  // Validate unavailable_reasons is strictly an array containing only contract-supported reason codes
  if (!Array.isArray(data.unavailable_reasons)) {
    throw new ApiClientError(502, 'Invalid API response envelope: unavailable_reasons must be an array.');
  }

  for (const reason of data.unavailable_reasons) {
    if (typeof reason !== 'string' || !VALID_UNAVAILABLE_REASONS.has(reason)) {
      throw new ApiClientError(
        502,
        `Invalid API response envelope: unsupported unavailable reason "${String(reason)}".`
      );
    }
  }

  return response as ApiAvailabilityResponse;
}
