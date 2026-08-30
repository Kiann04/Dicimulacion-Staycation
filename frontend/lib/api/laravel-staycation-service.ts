import { apiFetch, ApiClientError } from './http-client.ts';
import {
  validateApiAvailability,
  validateApiStaycationCollection,
  validateApiStaycationSingle,
} from './types.ts';
import {
  mapApiAvailabilityToResult,
  mapApiStaycationCollectionToResult,
  mapApiStaycationToDetails,
  mapApiStaycationToSummary,
} from './adapters.ts';
import type {
  AvailabilityResult,
  StaycationCollectionResult,
  StaycationDetails,
  StaycationFilter,
  StaycationSummary,
} from '../types/staycation.ts';
import type { StaycationService } from './staycation-service.ts';

const POSITIVE_INTEGER_REGEX = /^[1-9]\d*$/;

/**
 * Validates raw staycation identifier.
 * Requires the complete raw string to match positive integer without trim().
 */
export function isValidStaycationId(id: string | number | undefined | null): boolean {
  if (typeof id === 'number') {
    return Number.isInteger(id) && id > 0;
  }
  if (typeof id === 'string') {
    return POSITIVE_INTEGER_REGEX.test(id);
  }
  return false;
}

/**
 * Real Laravel REST API implementation of StaycationService for /api/v1.
 */
export class LaravelStaycationService implements StaycationService {
  /**
   * Fetches staycations from the public collection for initial presentation.
   */
  async getFeaturedStaycations(): Promise<StaycationSummary[]> {
    const raw = await apiFetch<unknown>(
      '/api/v1/staycations?per_page=6',
      {
        next: { revalidate: 60 },
      }
    );

    const validated = validateApiStaycationCollection(raw);
    return validated.data.map(mapApiStaycationToSummary);
  }

  /**
   * Fetches staycations collection with pagination metadata.
   */
  async getStaycationCollection(filter?: StaycationFilter): Promise<StaycationCollectionResult> {
    const raw = await apiFetch<unknown>(
      '/api/v1/staycations?per_page=50',
      {
        next: { revalidate: 60 },
      }
    );

    const validated = validateApiStaycationCollection(raw);
    const result = mapApiStaycationCollectionToResult(validated);

    if (filter) {
      let filteredItems = result.items;

      if (filter.query) {
        const q = filter.query.toLowerCase().trim();
        filteredItems = filteredItems.filter(
          (item) =>
            item.title.toLowerCase().includes(q) ||
            (item.description && item.description.toLowerCase().includes(q)) ||
            (item.location?.city && item.location.city.toLowerCase().includes(q))
        );
      }

      if (filter.city) {
        const cityLower = filter.city.toLowerCase().trim();
        filteredItems = filteredItems.filter(
          (item) => item.location?.city?.toLowerCase() === cityLower
        );
      }

      if (filter.guests) {
        filteredItems = filteredItems.filter((item) => (item.maxGuests ?? 1) >= (filter.guests || 1));
      }

      if (filter.minPrice) {
        filteredItems = filteredItems.filter((item) => Number(item.pricePerNight) >= (filter.minPrice || 0));
      }

      if (filter.maxPrice) {
        filteredItems = filteredItems.filter((item) => Number(item.pricePerNight) <= (filter.maxPrice || Infinity));
      }

      if (filter.sortBy) {
        if (filter.sortBy === 'price_asc') {
          filteredItems.sort((a, b) => Number(a.pricePerNight) - Number(b.pricePerNight));
        } else if (filter.sortBy === 'price_desc') {
          filteredItems.sort((a, b) => Number(b.pricePerNight) - Number(a.pricePerNight));
        } else if (filter.sortBy === 'rating') {
          filteredItems.sort((a, b) => (b.reviews?.rating ?? 0) - (a.reviews?.rating ?? 0));
        }
      }

      result.items = filteredItems;
    }

    return result;
  }

  /**
   * Legacy wrapper returning items array for backward compatibility.
   */
  async getStaycations(filter?: StaycationFilter): Promise<StaycationSummary[]> {
    const res = await this.getStaycationCollection(filter);
    return res.items;
  }

  /**
   * Fetches details for a specific staycation by numeric primary key.
   * Rejects non-positive-integer raw IDs immediately.
   */
  async getStaycationById(id: string | number): Promise<StaycationDetails | null> {
    if (!isValidStaycationId(id)) {
      return null;
    }

    const numericId = typeof id === 'number' ? id : parseInt(id, 10);

    try {
      const raw = await apiFetch<unknown>(
        `/api/v1/staycations/${numericId}`,
        {
          next: { revalidate: 60 },
        }
      );

      const validated = validateApiStaycationSingle(raw);
      return mapApiStaycationToDetails(validated.data);
    } catch (error) {
      if (error instanceof ApiClientError && error.isNotFound) {
        return null;
      }
      throw error;
    }
  }

  /**
   * Checks real-time date availability against GET /api/v1/staycations/{id}/availability.
   * Uses cache: 'no-store' to guarantee fresh inventory checks.
   */
  async checkAvailability(
    staycationId: string | number,
    checkIn: string,
    checkOut: string,
    guests: number
  ): Promise<AvailabilityResult> {
    if (!isValidStaycationId(staycationId)) {
      return {
        staycationId,
        checkInDate: checkIn,
        checkOutDate: checkOut,
        guests,
        isAvailable: false,
        status: 'error',
        message: 'Invalid staycation identifier.',
        isInformationalOnly: true,
      };
    }

    const numericId = typeof staycationId === 'number' ? staycationId : parseInt(staycationId, 10);

    // Client-side date check
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
      return {
        staycationId,
        checkInDate: checkIn,
        checkOutDate: checkOut,
        guests,
        isAvailable: false,
        status: 'conflict',
        message: 'Please select a valid checkout date after your arrival date.',
        isInformationalOnly: true,
      };
    }

    try {
      const availabilityPromise = apiFetch<unknown>(
        `/api/v1/staycations/${numericId}/availability?start_date=${encodeURIComponent(checkIn)}&end_date=${encodeURIComponent(checkOut)}`,
        {
          cache: 'no-store',
        }
      );

      const staycationPromise = this.getStaycationById(numericId);

      const [rawAvail, staycation] = await Promise.all([
        availabilityPromise,
        staycationPromise,
      ]);

      const validatedAvail = validateApiAvailability(rawAvail);

      if (!staycation) {
        return {
          staycationId,
          checkInDate: checkIn,
          checkOutDate: checkOut,
          guests,
          isAvailable: false,
          status: 'error',
          message: 'Staycation property not found.',
          isInformationalOnly: true,
        };
      }

      // Guest capacity validation (distinct frontend state)
      const maxGuests = staycation.capacity?.maximumGuests ?? staycation.maxGuests ?? 1;
      if (guests > maxGuests) {
        return {
          staycationId,
          checkInDate: checkIn,
          checkOutDate: checkOut,
          guests,
          isAvailable: false,
          status: 'unavailable',
          message: `Maximum guest capacity for this property is ${maxGuests} guests.`,
          isInformationalOnly: true,
        };
      }

      return mapApiAvailabilityToResult(
        validatedAvail.data,
        {
          price_per_night: String(staycation.pricePerNight),
          price_per_night_centavos: staycation.pricePerNightCentavos,
          currency: staycation.currency,
        },
        guests
      );
    } catch (error) {
      if (error instanceof ApiClientError) {
        if (error.isValidationError) {
          const detailMsg =
            error.errors?.start_date?.[0] ||
            error.errors?.end_date?.[0] ||
            error.message;
          return {
            staycationId,
            checkInDate: checkIn,
            checkOutDate: checkOut,
            guests,
            isAvailable: false,
            status: 'conflict',
            message: detailMsg || 'The requested date range is not valid.',
            isInformationalOnly: true,
          };
        }

        if (error.isNotFound) {
          return {
            staycationId,
            checkInDate: checkIn,
            checkOutDate: checkOut,
            guests,
            isAvailable: false,
            status: 'error',
            message: 'Staycation not found.',
            isInformationalOnly: true,
          };
        }

        if (error.isRateLimited) {
          return {
            staycationId,
            checkInDate: checkIn,
            checkOutDate: checkOut,
            guests,
            isAvailable: false,
            status: 'error',
            message: 'Too many requests. Please wait a moment and try again.',
            isInformationalOnly: true,
          };
        }

        if (error.status === 502) {
          return {
            staycationId,
            checkInDate: checkIn,
            checkOutDate: checkOut,
            guests,
            isAvailable: false,
            status: 'error',
            message: error.message,
            isInformationalOnly: true,
          };
        }
      }

      return {
        staycationId,
        checkInDate: checkIn,
        checkOutDate: checkOut,
        guests,
        isAvailable: false,
        status: 'error',
        message:
          error instanceof ApiClientError
            ? error.message
            : 'Unable to connect to the staycation service. Please verify the API is reachable.',
        isInformationalOnly: true,
      };
    }
  }
}
