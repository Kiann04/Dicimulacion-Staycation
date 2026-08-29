import { apiClient } from "../api/client";
import {
  Booking,
  QuoteResponse,
  PaginatedResponse,
  ApiResponse,
  PaymentMethod,
  PaymentType,
} from "../types";
import { normalizeStaycation } from "./staycationService";

export interface BookingSubmissionPayload {
  staycation_id: number;
  start_date: string;
  end_date: string;
  guest_number: number;
  phone: string;
  payment_type: PaymentType;
  payment_method: PaymentMethod;
  payment_proof: File;
  transaction_number?: string;
  message_to_admin?: string;
}

export function normalizeBooking(item: any): Booking {
  if (!item) return item;

  const totalNum =
    item.pricing?.total_price !== undefined
      ? parseFloat(item.pricing.total_price) || 0
      : parseFloat(item.total_price) || 0;

  const paidNum =
    item.pricing?.amount_paid !== undefined
      ? parseFloat(item.pricing.amount_paid) || 0
      : parseFloat(item.amount_paid) || 0;

  const pricePerDayNum =
    item.pricing?.price_per_night !== undefined
      ? parseFloat(item.pricing.price_per_night) || 0
      : parseFloat(item.price_per_day) || 0;

  const startDateStr = item.stay?.start_date || item.start_date || "";
  const endDateStr = item.stay?.end_date || item.end_date || "";
  const guestNum = item.guest?.guest_number || item.guest_number || 1;
  const guestPhone = item.guest?.phone || item.phone || "";
  const guestName = item.guest?.name || item.name || "";
  const guestEmail = item.guest?.email || item.email || "";

  const paymentStatus = item.payment?.status || item.payment_status || "pending";
  const paymentMethod = item.payment?.method || item.payment_method || "gcash";

  return {
    ...item,
    id: item.id,
    reference: item.reference || `BK-${String(item.id).padStart(6, "0")}`,
    status: item.status || "pending",
    blocks_availability: item.blocks_availability ?? true,
    guest: {
      name: guestName,
      email: guestEmail,
      phone: guestPhone,
      guest_number: guestNum,
    },
    stay: {
      start_date: startDateStr,
      end_date: endDateStr,
      nights: item.stay?.nights ?? (startDateStr && endDateStr ? Math.max(1, Math.round((new Date(endDateStr).getTime() - new Date(startDateStr).getTime()) / 86400000)) : 1),
    },
    pricing: {
      price_per_night: item.pricing?.price_per_night || pricePerDayNum.toFixed(2),
      total_price: item.pricing?.total_price || totalNum.toFixed(2),
      amount_paid: item.pricing?.amount_paid || paidNum.toFixed(2),
      balance_due: item.pricing?.balance_due || Math.max(0, totalNum - paidNum).toFixed(2),
      currency: item.pricing?.currency || "PHP",
    },
    payment: {
      status: paymentStatus,
      method: paymentMethod,
      transaction_number: item.payment?.transaction_number || item.transaction_number || null,
      proof_url: item.payment?.proof_url || null,
    },
    message_to_admin: item.message_to_admin || null,
    staycation: item.staycation ? normalizeStaycation(item.staycation) : undefined,
    payments: item.payments || [],
    can: item.can || {
      cancel: item.status === "pending" || item.status === "approved" || item.status === "waiting",
    },
    created_at: item.created_at,
    updated_at: item.updated_at,

    // Backward compatibility getters
    name: guestName,
    email: guestEmail,
    phone: guestPhone,
    guest_number: guestNum,
    start_date: startDateStr,
    end_date: endDateStr,
    price_per_day: pricePerDayNum,
    total_price: totalNum,
    amount_paid: paidNum,
    payment_status: paymentStatus,
    payment_method: paymentMethod,
    transaction_number: item.payment?.transaction_number || item.transaction_number || null,
  };
}

export const bookingService = {
  /**
   * Request authoritative availability, pricing and deposit calculation from Laravel.
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
   * Submit a booking reservation request with multipart payment proof screenshot.
   */
  async submit(formData: FormData): Promise<Booking> {
    const res = await apiClient<ApiResponse<any>>("bookings", {
      method: "POST",
      data: formData,
    });
    return normalizeBooking(res.data);
  },

  /**
   * Fetch authenticated customer's own booking history.
   */
  async getHistory(params?: { status?: string; page?: number; per_page?: number }): Promise<Booking[]> {
    const res = await apiClient<PaginatedResponse<any> | { data: any[] }>("bookings", {
      params,
    });
    const list = Array.isArray(res.data) ? res.data : [];
    return list.map(normalizeBooking);
  },

  /**
   * Fetch details of a single booking by ID.
   */
  async getById(bookingId: number | string): Promise<Booking> {
    const res = await apiClient<ApiResponse<any>>(`bookings/${bookingId}`);
    return normalizeBooking(res.data);
  },

  /**
   * Customer cancellation of a pending or approved booking.
   */
  async cancel(bookingId: number | string): Promise<Booking> {
    const res = await apiClient<ApiResponse<any>>(`bookings/${bookingId}`, {
      method: "DELETE",
    });
    return normalizeBooking(res.data);
  },
};
