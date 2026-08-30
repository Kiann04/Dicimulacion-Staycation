/**
 * Typed domain models for Staycation frontend UI.
 * 
 * Preserves authoritative backend data without inventing uncontracted facts or fee math.
 */

export interface StaycationImage {
  id: string | number;
  url: string;
  alt?: string;
  isPrimary?: boolean;
  caption?: string;
  isPlaceholder?: boolean;
}

export interface StaycationAmenity {
  id: string | number;
  name: string;
  category?: string;
  icon?: string;
}

export interface StaycationHost {
  name: string;
  avatarUrl?: string;
  isSuperhost?: boolean;
  joinedDate?: string;
  responseRate?: string;
}

export interface StaycationReviewSummary {
  rating?: number | null; // e.g. 4.5 or null
  reviewCount?: number; // e.g. 12 or 0
  cleanlinessRating?: number;
  accuracyRating?: number;
  locationRating?: number;
  valueRating?: number;
}

export interface StaycationLocation {
  city: string;
  province?: string;
  neighborhood?: string;
  country?: string;
  address?: string;
}

export interface StaycationCapacity {
  includedGuests: number;
  maximumGuests: number;
  extraGuestFee: string; // exact decimal string e.g. "500.00"
  extraGuestFeeCentavos: number; // integer centavos e.g. 50000
}

export interface StaycationSummary {
  id: number | string;
  slug?: string;
  title: string;
  description?: string;
  tagline?: string;
  location?: StaycationLocation;
  coverImage?: StaycationImage;
  images?: StaycationImage[];
  pricePerNight: string | number; // exact decimal string from Laravel
  pricePerNightCentavos?: number; // exact integer centavos from Laravel
  originalPricePerNight?: string | number;
  currency?: string; // "PHP"
  capacity?: StaycationCapacity;
  maxGuests?: number;
  bedrooms?: number;
  beds?: number;
  bathrooms?: number;
  propertyType?: string;
  isAvailable?: boolean; // is_bookable from contract
  availabilityStatus?: 'available' | 'unavailable' | string;
  featured?: boolean;
  badge?: string;
  reviews?: StaycationReviewSummary;
  amenityHighlights?: string[];
}

export interface StaycationDetails extends StaycationSummary {
  spaceDescription?: string;
  guestAccessDescription?: string;
  houseRules?: string[];
  checkInTime?: string;
  checkOutTime?: string;
  host?: StaycationHost;
  amenities?: StaycationAmenity[];
  cancellationPolicy?: string;
  safetyInfo?: string[];
}

export interface StaycationPaginationMeta {
  currentPage: number;
  from: number | null;
  lastPage: number;
  perPage: number;
  to: number | null;
  total: number;
}

export interface StaycationCollectionResult {
  items: StaycationSummary[];
  meta?: StaycationPaginationMeta;
}

export interface StaycationFilter {
  query?: string;
  city?: string;
  propertyType?: string;
  minPrice?: number;
  maxPrice?: number;
  guests?: number;
  amenities?: string[];
  sortBy?: 'recommended' | 'price_asc' | 'price_desc' | 'rating';
}

export interface AvailabilityResult {
  staycationId: number | string;
  checkInDate: string;
  checkOutDate: string;
  guests: number;
  isAvailable: boolean;
  status?: 'available' | 'unavailable' | 'conflict' | 'blocked' | 'error';
  message: string;
  isInformationalOnly: boolean;
  nights?: number;
  pricePerNight?: string | number;
  pricePerNightCentavos?: number;
  currency?: string;
  notice?: string;
}
