import { apiClient } from "../api/client";
import {
  Staycation,
  AvailabilityResponse,
  QuoteResponse,
  CalendarEvent,
  PaginatedResponse,
  ApiResponse,
} from "../types";

/**
 * Normalizes a Staycation resource from the Laravel API into a consistent shape
 * with backward-compatible aliases for existing frontend UI components.
 */
export function normalizeStaycation(item: any): Staycation {
  if (!item) return item;

  const priceNumber =
    typeof item.price_per_night === "string"
      ? parseFloat(item.price_per_night) || 0
      : item.price_per_night || item.house_price || 0;

  const primaryImage =
    item.image_url ||
    item.house_image ||
    (item.images && item.images.length > 0 ? item.images[0].url || item.images[0].image_url : null) ||
    "/assets/placeholder.jpg";

  const ratingAvg = item.rating?.average ?? item.average_rating ?? 4.9;
  const ratingCount = item.rating?.count ?? item.total_reviews ?? 24;

  return {
    ...item,
    id: item.id,
    name: item.name || item.house_name || "Staycation Villa",
    description: item.description || item.house_description || "",
    location: item.location || item.house_location || "Tagaytay, Cavite",
    price_per_night: String(item.price_per_night || priceNumber.toFixed(2)),
    currency: item.currency || "PHP",
    availability: item.availability || item.house_availability || "available",
    is_bookable: item.is_bookable !== undefined ? item.is_bookable : true,
    image_url: primaryImage,
    images: (item.images || []).map((img: any) => ({
      id: img.id,
      staycation_id: img.staycation_id || item.id,
      url: img.url || img.image_url || img.image_path,
      image_url: img.url || img.image_url || img.image_path,
    })),
    max_guests: item.max_guests || 8,
    rating: item.rating || { average: ratingAvg, count: ratingCount },

    // Compatibility fields
    house_name: item.name || item.house_name || "Staycation Villa",
    house_description: item.description || item.house_description || "",
    house_price: priceNumber,
    house_location: item.location || item.house_location || "Tagaytay, Cavite",
    house_availability: item.availability || item.house_availability || "available",
    house_image: primaryImage,
    average_rating: ratingAvg,
    total_reviews: ratingCount,
  };
}

export const staycationService = {
  /**
   * Fetch public staycation catalog with optional filters.
   */
  async getAll(params?: {
    available_only?: boolean | string;
    availability?: string;
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<Staycation[]> {
    const queryParams: Record<string, any> = {};

    if (params?.available_only || params?.availability === "available") {
      queryParams.available_only = true;
    }
    if (params?.search) {
      queryParams.search = params.search;
    }
    if (params?.page) queryParams.page = params.page;
    if (params?.per_page) queryParams.per_page = params.per_page;

    const res = await apiClient<PaginatedResponse<any> | { data: any[] }>(
      "staycations",
      { params: queryParams }
    );

    const list = Array.isArray(res.data) ? res.data : [];
    return list.map(normalizeStaycation);
  },

  /**
   * Fetch single listing details by ID.
   */
  async getById(id: number | string): Promise<Staycation> {
    const res = await apiClient<ApiResponse<any> | { data: any }>(
      `staycations/${id}`
    );
    return normalizeStaycation(res.data);
  },

  /**
   * Check date availability for a listing.
   */
  async getAvailability(
    staycationId: number | string,
    startDate: string,
    endDate: string
  ): Promise<AvailabilityResponse> {
    const res = await apiClient<ApiResponse<AvailabilityResponse>>(
      `staycations/${staycationId}/availability`,
      {
        params: {
          start_date: startDate,
          end_date: endDate,
        },
      }
    );
    return res.data;
  },

  /**
   * Request authoritative quote calculation from backend.
   */
  async getQuote(
    staycationId: number | string,
    payload: {
      start_date: string;
      end_date: string;
      guest_number: number;
    }
  ): Promise<QuoteResponse> {
    const res = await apiClient<ApiResponse<QuoteResponse>>(
      `staycations/${staycationId}/quote`,
      {
        method: "POST",
        data: payload,
      }
    );
    return res.data;
  },

  /**
   * Fetch blocked dates and active bookings as calendar events for a listing.
   */
  async getCalendarEvents(staycationId: number | string): Promise<CalendarEvent[]> {
    try {
      const res = await apiClient<ApiResponse<any>>(`admin/blocked-dates`, {
        params: { staycation_id: staycationId },
      });
      const blockedDates = Array.isArray(res.data) ? res.data : [];
      return blockedDates.map((b: any) => ({
        title: b.reason || "Unavailable",
        start: b.start_date,
        end: b.end_date,
        display: "background",
        color: "#6b7280",
        className: "blocked-date",
      }));
    } catch {
      return [];
    }
  },

  // Admin Mutations
  async create(formData: FormData): Promise<Staycation> {
    const res = await apiClient<ApiResponse<any>>("admin/staycations", {
      method: "POST",
      data: formData,
    });
    return normalizeStaycation(res.data);
  },

  async update(
    id: number | string,
    formData: FormData
  ): Promise<{ success: boolean; message?: string }> {
    return await apiClient(`admin/staycations/${id}`, {
      method: "PUT",
      data: formData,
    });
  },

  async toggleAvailability(
    id: number | string
  ): Promise<{ success: boolean; data?: any }> {
    return await apiClient(`admin/staycations/${id}/toggle-availability`, {
      method: "POST",
    });
  },
};
