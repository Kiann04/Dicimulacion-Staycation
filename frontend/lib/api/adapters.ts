import type {
  ApiAvailabilityData,
  ApiStaycation,
  ApiStaycationCollectionResponse,
  ApiUnavailableReason,
} from './types.ts';
import type {
  AvailabilityResult,
  StaycationCapacity,
  StaycationCollectionResult,
  StaycationDetails,
  StaycationImage,
  StaycationSummary,
} from '../types/staycation.ts';

/**
 * Maps an ApiStaycation DTO to a frontend StaycationSummary model.
 * Strictly preserves authoritative backend data without inventing property facts or fees.
 */
export function mapApiStaycationToSummary(api: ApiStaycation): StaycationSummary {
  const images: StaycationImage[] = [];

  if (api.image_url) {
    images.push({
      id: `main-${api.id}`,
      url: api.image_url,
      alt: api.name,
      isPrimary: true,
    });
  }

  if (Array.isArray(api.gallery)) {
    api.gallery.forEach((url, index) => {
      images.push({
        id: `gallery-${api.id}-${index}`,
        url,
        alt: `${api.name} photo ${index + 1}`,
      });
    });
  }

  const coverImage: StaycationImage | undefined =
    images.length > 0 ? images[0] : undefined;

  const capacity: StaycationCapacity | undefined = api.capacity
    ? {
        includedGuests: api.capacity.included_guests,
        maximumGuests: api.capacity.maximum_guests,
        extraGuestFee: api.capacity.extra_guest_fee,
        extraGuestFeeCentavos: api.capacity.extra_guest_fee_centavos,
      }
    : undefined;

  return {
    id: api.id,
    title: api.name,
    description: api.description,
    location: {
      city: api.location,
    },
    coverImage,
    images,
    pricePerNight: api.price_per_night, // Exact decimal string from Laravel e.g. "4500.00"
    pricePerNightCentavos: api.price_per_night_centavos, // Exact integer centavos e.g. 450000
    currency: api.currency || 'PHP',
    capacity,
    maxGuests: api.capacity?.maximum_guests ?? api.capacity?.included_guests,
    isAvailable: api.is_bookable,
    availabilityStatus: api.availability_status,
    reviews: {
      rating: api.rating?.average ?? null,
      reviewCount: api.rating?.count ?? 0,
    },
  };
}

/**
 * Maps an ApiStaycationCollectionResponse to StaycationCollectionResult with preserved pagination meta.
 */
export function mapApiStaycationCollectionToResult(
  api: ApiStaycationCollectionResponse
): StaycationCollectionResult {
  return {
    items: api.data.map(mapApiStaycationToSummary),
    meta: {
      currentPage: api.meta.current_page,
      from: api.meta.from,
      lastPage: api.meta.last_page,
      perPage: api.meta.per_page,
      to: api.meta.to,
      total: api.meta.total,
    },
  };
}

/**
 * Maps an ApiStaycation DTO to a frontend StaycationDetails model.
 */
export function mapApiStaycationToDetails(api: ApiStaycation): StaycationDetails {
  const summary = mapApiStaycationToSummary(api);

  return {
    ...summary,
  };
}

/**
 * Human-readable message translations for backend unavailable reason codes.
 */
export function translateUnavailableReasons(reasons: ApiUnavailableReason[]): string {
  if (!reasons || reasons.length === 0) {
    return 'The selected dates are unavailable.';
  }

  const messages: string[] = [];

  for (const reason of reasons) {
    switch (reason) {
      case 'property_unavailable':
        messages.push('This property is currently not accepting new bookings.');
        break;
      case 'booking_conflict':
        messages.push('The selected dates conflict with an existing reservation.');
        break;
      case 'blocked_dates':
        messages.push('The selected dates are blocked on the calendar.');
        break;
      default:
        messages.push('The property is unavailable for the selected range.');
        break;
    }
  }

  return messages.join(' ');
}

/**
 * Maps an ApiAvailabilityData DTO to a frontend AvailabilityResult model.
 * Does NOT invent taxes, cleaning fees, service fees, or total stay calculations.
 */
export function mapApiAvailabilityToResult(
  data: ApiAvailabilityData,
  staycation: {
    price_per_night: string;
    price_per_night_centavos?: number;
    currency?: string;
  },
  guests: number
): AvailabilityResult {
  const isAvailable = data.available;
  let status: AvailabilityResult['status'] = 'available';

  if (!isAvailable) {
    if (data.unavailable_reasons.includes('property_unavailable')) {
      status = 'unavailable';
    } else if (data.unavailable_reasons.includes('booking_conflict')) {
      status = 'conflict';
    } else if (data.unavailable_reasons.includes('blocked_dates')) {
      status = 'blocked';
    } else {
      status = 'unavailable';
    }
  }

  const message = isAvailable
    ? 'Dates are available for booking!'
    : translateUnavailableReasons(data.unavailable_reasons);

  return {
    staycationId: data.staycation_id,
    checkInDate: data.start_date,
    checkOutDate: data.end_date,
    guests,
    isAvailable,
    status,
    message,
    isInformationalOnly: true, // reserves_inventory is false in API contract
    nights: data.nights,
    pricePerNight: staycation.price_per_night,
    pricePerNightCentavos: staycation.price_per_night_centavos,
    currency: staycation.currency || 'PHP',
    notice: 'Final pricing and fees will be confirmed during reservation.',
  };
}
