import type {
  AvailabilityResult,
  StaycationCollectionResult,
  StaycationDetails,
  StaycationFilter,
  StaycationSummary,
} from '../types/staycation.ts';
import { LaravelStaycationService } from './laravel-staycation-service.ts';
import { MOCK_STAYCATIONS, getStaycationSummary } from '../mocks/staycations.ts';
import { calculateNights } from '../utils/formatters.ts';

export interface StaycationService {
  getFeaturedStaycations(): Promise<StaycationSummary[]>;
  getStaycations(filter?: StaycationFilter): Promise<StaycationSummary[]>;
  getStaycationCollection(filter?: StaycationFilter): Promise<StaycationCollectionResult>;
  getStaycationById(id: string | number): Promise<StaycationDetails | null>;
  checkAvailability(
    staycationId: string | number,
    checkIn: string,
    checkOut: string,
    guests: number
  ): Promise<AvailabilityResult>;
}

/**
 * Mock implementation of StaycationService preserved for tests, Storybook/demos,
 * or explicit mock development mode (NEXT_PUBLIC_USE_MOCK_API=true).
 */
export class MockStaycationService implements StaycationService {
  private simulateDelay(ms = 80): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  async getFeaturedStaycations(): Promise<StaycationSummary[]> {
    await this.simulateDelay();
    return MOCK_STAYCATIONS.filter((item) => item.featured).map(getStaycationSummary);
  }

  async getStaycationCollection(filter?: StaycationFilter): Promise<StaycationCollectionResult> {
    const items = await this.getStaycations(filter);
    return {
      items,
      meta: {
        currentPage: 1,
        from: 1,
        lastPage: 1,
        perPage: 50,
        to: items.length,
        total: MOCK_STAYCATIONS.length,
      },
    };
  }

  async getStaycations(filter?: StaycationFilter): Promise<StaycationSummary[]> {
    await this.simulateDelay();
    let results = [...MOCK_STAYCATIONS];

    if (filter) {
      if (filter.query) {
        const q = filter.query.toLowerCase().trim();
        results = results.filter(
          (item) =>
            item.title.toLowerCase().includes(q) ||
            (item.tagline && item.tagline.toLowerCase().includes(q)) ||
            (item.location?.city && item.location.city.toLowerCase().includes(q)) ||
            (item.location?.province && item.location.province.toLowerCase().includes(q))
        );
      }

      if (filter.city) {
        results = results.filter(
          (item) => item.location?.city?.toLowerCase() === filter.city?.toLowerCase()
        );
      }

      if (filter.guests) {
        results = results.filter((item) => (item.maxGuests ?? 1) >= (filter.guests || 1));
      }

      if (filter.minPrice) {
        results = results.filter((item) => Number(item.pricePerNight) >= (filter.minPrice || 0));
      }

      if (filter.maxPrice) {
        results = results.filter((item) => Number(item.pricePerNight) <= (filter.maxPrice || Infinity));
      }

      if (filter.sortBy) {
        if (filter.sortBy === 'price_asc') {
          results.sort((a, b) => Number(a.pricePerNight) - Number(b.pricePerNight));
        } else if (filter.sortBy === 'price_desc') {
          results.sort((a, b) => Number(b.pricePerNight) - Number(a.pricePerNight));
        } else if (filter.sortBy === 'rating') {
          results.sort((a, b) => (b.reviews?.rating ?? 0) - (a.reviews?.rating ?? 0));
        }
      }
    }

    return results.map(getStaycationSummary);
  }

  async getStaycationById(id: string | number): Promise<StaycationDetails | null> {
    await this.simulateDelay();
    const strId = String(id).toLowerCase();
    const item = MOCK_STAYCATIONS.find(
      (stay) =>
        String(stay.id).toLowerCase() === strId ||
        (stay.slug && stay.slug.toLowerCase() === strId)
    );
    return item ? { ...item } : null;
  }

  async checkAvailability(
    staycationId: string | number,
    checkIn: string,
    checkOut: string,
    guests: number
  ): Promise<AvailabilityResult> {
    await this.simulateDelay(120);
    const item = await this.getStaycationById(staycationId);

    if (!item) {
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

    const startDate = new Date(checkIn);
    const endDate = new Date(checkOut);
    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime()) || endDate <= startDate) {
      return {
        staycationId,
        checkInDate: checkIn,
        checkOutDate: checkOut,
        guests,
        isAvailable: false,
        status: 'conflict',
        message: 'Please select a valid check-out date after your check-in date.',
        isInformationalOnly: true,
      };
    }

    const maxGuests = item.maxGuests ?? 1;
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

    const nights = calculateNights(checkIn, checkOut);
    const pricePerNight = item.pricePerNight;

    return {
      staycationId,
      checkInDate: checkIn,
      checkOutDate: checkOut,
      guests,
      isAvailable: true,
      status: 'available',
      message: 'Dates are available for booking!',
      isInformationalOnly: true,
      nights,
      pricePerNight,
      currency: item.currency || 'PHP',
      notice: 'Final pricing and fees will be confirmed during reservation.',
    };
  }
}

/**
 * Service factory:
 * Uses LaravelStaycationService by default.
 * Falls back to MockStaycationService ONLY if explicitly requested via NEXT_PUBLIC_USE_MOCK_API=true.
 */
function createStaycationService(): StaycationService {
  if (process.env.NEXT_PUBLIC_USE_MOCK_API === 'true') {
    return new MockStaycationService();
  }
  return new LaravelStaycationService();
}

export const staycationService: StaycationService = createStaycationService();
