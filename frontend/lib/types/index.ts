// Core TypeScript Interfaces matching docs/api-contract.md (Laravel API v1)

export type UserRole = "user" | "admin" | "staff";

export interface User {
  id: number;
  name: string;
  email: string;
  role?: UserRole;
  usertype?: UserRole; // Alias for backward compatibility
  email_verified?: boolean;
  profile_photo_url?: string | null;
  two_factor_enabled?: boolean;
  created_at?: string;
}

export interface StaycationImage {
  id: number;
  staycation_id?: number;
  url?: string;
  image_url?: string; // Alias
  image_path?: string; // Legacy alias
  order?: number;
}

export interface StaycationRating {
  average: number;
  count: number;
}

export interface Review {
  id: number;
  user_id: number;
  booking_id: number;
  staycation_id?: number;
  rating: number; // 1 to 5
  comment: string;
  created_at: string;
  user?: Pick<User, "id" | "name" | "profile_photo_url">;
  booking?: {
    id: number;
    staycation?: Pick<Staycation, "id" | "name" | "house_name">;
  };
}

export interface Staycation {
  id: number;
  name?: string;
  description?: string;
  location?: string;
  price_per_night?: string;
  currency?: string;
  availability?: "available" | "unavailable";
  is_bookable?: boolean;
  image_url?: string | null;
  images?: StaycationImage[];
  max_guests?: number;
  rating?: StaycationRating | null;
  created_at?: string;

  // Compatibility aliases for existing UI components
  house_name?: string;
  house_description?: string;
  house_price?: number;
  house_location?: string;
  house_availability?: "available" | "unavailable";
  house_image?: string;
  average_rating?: number;
  total_reviews?: number;
  star_counts?: Record<string, number>;
  reviews?: Review[];
}

export interface DateConflict {
  type: string;
  start_date: string;
  end_date: string;
  reason: string | null;
}

export interface AvailabilityResponse {
  staycation_id: number;
  start_date: string;
  end_date: string;
  nights: number;
  is_available: boolean;
  is_bookable: boolean;
  conflicts: DateConflict[];
}

export interface StaycationQuote {
  start_date: string;
  end_date: string;
  nights: number;
  guest_number: number;
  price_per_night: string;
  accommodation_total: string;
  extra_guests: number;
  extra_guest_fee: string;
  total_price: string;
  deposit_amount: string;
  balance_due: string;
  currency: string;
}

export interface QuoteResponse {
  staycation_id: number;
  is_available: boolean;
  conflicts: DateConflict[];
  quote: StaycationQuote;
}

export type BookingStatus =
  | "waiting"
  | "pending"
  | "approved"
  | "confirmed"
  | "completed"
  | "declined"
  | "cancelled";

export type PaymentStatus =
  | "unpaid"
  | "pending"
  | "half_paid"
  | "paid"
  | "failed"
  | "refunded";

export type PaymentMethod = "gcash" | "bpi" | "cash";
export type PaymentType = "half" | "full";

export interface BookingGuest {
  name: string;
  email?: string | null;
  phone: string;
  guest_number: number;
}

export interface BookingStay {
  start_date: string | null;
  end_date: string | null;
  nights: number | null;
}

export interface BookingPricing {
  price_per_night: string;
  total_price: string;
  amount_paid: string;
  balance_due: string;
  currency: string;
}

export interface BookingPayment {
  status: PaymentStatus;
  method: PaymentMethod;
  transaction_number?: string | null;
  proof_url?: string | null;
}

export interface BookingLedgerEntry {
  id: number;
  amount: string;
  type: "deposit" | "balance" | "full" | "refund";
  status: string;
  payment_method: string;
  reference_number?: string | null;
  has_proof: boolean;
  verified_at?: string | null;
  created_at: string;
}

export interface Booking {
  id: number;
  reference?: string;
  status: BookingStatus;
  blocks_availability?: boolean;
  guest?: BookingGuest;
  stay?: BookingStay;
  pricing?: BookingPricing;
  payment?: BookingPayment;
  message_to_admin?: string | null;
  staycation?: Staycation;
  payments?: BookingLedgerEntry[];
  can?: {
    cancel: boolean;
  };
  created_at?: string;
  updated_at?: string;

  // Compatibility aliases
  staycation_id?: number;
  user_id?: number;
  name?: string;
  email?: string;
  phone?: string;
  guest_number?: number;
  start_date?: string;
  end_date?: string;
  formatted_start_date?: string;
  formatted_end_date?: string;
  price_per_day?: number;
  total_price?: number;
  amount_paid?: number;
  payment_status?: PaymentStatus;
  payment_method?: PaymentMethod;
  payment_proof?: string | null;
  transaction_number?: string | null;
  has_review?: boolean;
  review?: Review | null;
  user?: User;
}

export interface BlockedDate {
  id: number;
  staycation_id: number;
  start_date: string;
  end_date: string;
  reason?: string | null;
  staycation?: Staycation;
}

export interface CalendarEvent {
  title: string;
  start: string;
  end: string;
  display?: string;
  color?: string;
  className?: string;
}

export interface Inquiry {
  id: number;
  email: string;
  message: string;
  attachment?: string | null;
  status?: "unread" | "read";
  created_at: string;
  updated_at?: string;
}

export interface AuditLog {
  id: number;
  user_id?: number;
  action: string;
  description: string;
  ip_address: string;
  created_at: string;
  user?: Pick<User, "id" | "name" | "email">;
}

export interface AdminDashboardStats {
  bookings?: {
    total: number;
    pending: number;
    approved: number;
    confirmed: number;
    cancelled: number;
    declined: number;
    arriving_today: number;
    in_house: number;
  };
  payments?: {
    awaiting_verification: number;
    unpaid_bookings: number;
    half_paid_bookings: number;
    collected_total: string;
    expected_total: string;
    outstanding_total: string;
  };
  catalogue?: {
    staycations: number;
    available: number;
  };
  customers?: {
    total: number;
    staff: number;
  };
  generated_at?: string;

  // UI chart/summary helpers
  totalUsers: number;
  totalBookings: number;
  totalRevenue: number;
  monthlyBookings: number;
  monthlyRevenue: number;
  newUsers: number;
  averageOccupancy: string;
  unpaidCount: number;
  chart: {
    months: string[];
    totals: number[];
    revenues: number[];
  };
  recentBookings: Booking[];
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  error_code?: string;
  errors?: Record<string, string[]>;
  conflicts?: DateConflict[];
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  links?: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
}
