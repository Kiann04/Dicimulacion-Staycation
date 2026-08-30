import { MOCK_STAYCATIONS, getStaycationSummary } from '../mocks/staycations';
import {
  AvailabilityResult,
  StaycationDetails,
  StaycationFilter,
  StaycationSummary,
} from '../types/staycation';
import { calculateNights } from '../utils/formatters';

export interface StaycationService {
  getFeaturedStaycations(): Promise<StaycationSummary[]>;
  getStaycations(filter?: StaycationFilter): Promise<StaycationSummary[]>;
  getStaycationById(id: string | number): Promise<StaycationDetails | null>;
  checkAvailability(
    staycationId: string | number,
    checkIn: string,
    checkOut: string,
    guests: number
  ): Promise<AvailabilityResult>;
}

/**
 * Mock implementation of StaycationService for frontend foundation development.
 * 
 * SWAP STRATEGY:
 * When Laravel REST API endpoints are finalized, create `LaravelStaycationService`
 * adhering to the `StaycationService` interface and change the exported instance below.
 */
class MockStaycationService implements StaycationService {
  private simulateDelay(ms = 80): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  async getFeaturedStaycations(): Promise<StaycationSummary[]> {
    await this.simulateDelay();
    return MOCK_STAYCATIONS.filter((item) => item.featured).map(getStaycationSummary);
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
          (item) => item.location?.city.toLowerCase() === filter.city?.toLowerCase()
        );
      }

      if (filter.propertyType && filter.propertyType !== 'all') {
        results = results.filter(
          (item) => item.propertyType?.toLowerCase() === filter.propertyType?.toLowerCase()
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
      (stay) => String(stay.id).toLowerCase() === strId || (stay.slug && stay.slug.toLowerCase() === strId)
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
    const pricePerNight = Number(item.pricePerNight) || 0;
    const baseTotal = pricePerNight * nights;
    const cleaningFee = 1500;
    const serviceFee = Math.round(baseTotal * 0.08);
    const taxes = Math.round((baseTotal + cleaningFee + serviceFee) * 0.12);
    const total = baseTotal + cleaningFee + serviceFee + taxes;

    return {
      staycationId,
      checkInDate: checkIn,
      checkOutDate: checkOut,
      guests,
      isAvailable: true,
      status: 'available',
      message: 'Dates are available for booking!',
      isInformationalOnly: true,
      priceBreakdown: {
        nights,
        pricePerNight,
        baseTotal,
        cleaningFee,
        serviceFee,
        taxes,
        total,
        currency: item.currency || 'PHP',
      },
    };
  }
}

export const staycationService: StaycationService = new MockStaycationService();
