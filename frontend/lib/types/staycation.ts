/**
 * Typed domain models for Staycation frontend UI.
 * 
 * NOTE: These models reflect UI data needs during the foundation phase
 * and are decoupled from the eventual Laravel REST API schema.
 * All fields are structured with safe optionality so components
 * remain resilient as the backend contract is finalized.
 */

export interface StaycationImage {
  id: string | number;
  url: string;
  alt?: string;
  isPrimary?: boolean;
  caption?: string;
}

export interface StaycationAmenity {
  id: string | number;
  name: string;
  category?: 'essentials' | 'kitchen' | 'entertainment' | 'outdoor' | 'safety' | 'services' | string;
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
  rating?: number | null; // e.g. 4.92
  reviewCount?: number;
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
  latitude?: number;
  longitude?: number;
}

export interface StaycationSummary {
  id: string | number;
  slug?: string;
  title: string;
  tagline?: string;
  location?: StaycationLocation;
  coverImage?: StaycationImage;
  images?: StaycationImage[];
  pricePerNight: number | string;
  originalPricePerNight?: number | string;
  currency?: string;
  maxGuests?: number;
  bedrooms?: number;
  beds?: number;
  bathrooms?: number;
  propertyType?: string;
  isAvailable?: boolean;
  featured?: boolean;
  badge?: string;
  reviews?: StaycationReviewSummary;
  amenityHighlights?: string[];
}

export interface StaycationDetails extends StaycationSummary {
  description?: string;
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

export interface PriceBreakdown {
  nights: number;
  pricePerNight: number | string;
  baseTotal: number | string;
  cleaningFee?: number | string;
  serviceFee?: number | string;
  taxes?: number | string;
  total: number | string;
  currency?: string;
}

export interface AvailabilityResult {
  staycationId: string | number;
  checkInDate: string;
  checkOutDate: string;
  guests: number;
  isAvailable: boolean;
  status?: 'available' | 'unavailable' | 'conflict' | 'blocked' | 'error';
  message: string;
  isInformationalOnly: boolean;
  priceBreakdown?: PriceBreakdown;
}
