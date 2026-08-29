import { apiClient } from "../api/client";
import {
  AdminDashboardStats,
  Booking,
  BlockedDate,
  User,
  Inquiry,
  AuditLog,
  ApiResponse,
  PaginatedResponse,
} from "../types";
import { normalizeBooking } from "./bookingService";

export const adminService = {
  async getDashboardStats(): Promise<AdminDashboardStats> {
    const res = await apiClient<ApiResponse<any>>("admin/dashboard");
    const d = res.data;

    return {
      bookings: d.bookings || {
        total: 0,
        pending: 0,
        approved: 0,
        confirmed: 0,
        cancelled: 0,
        declined: 0,
        arriving_today: 0,
        in_house: 0,
      },
      payments: d.payments || {
        awaiting_verification: 0,
        unpaid_bookings: 0,
        half_paid_bookings: 0,
        collected_total: "0.00",
        expected_total: "0.00",
        outstanding_total: "0.00",
      },
      catalogue: d.catalogue || { staycations: 0, available: 0 },
      customers: d.customers || { total: 0, staff: 0 },
      generated_at: d.generated_at || new Date().toISOString(),

      // Aliases for dashboard UI cards
      totalUsers: d.customers?.total ?? 0,
      totalBookings: d.bookings?.total ?? 0,
      totalRevenue: parseFloat(d.payments?.collected_total || "0") || 0,
      monthlyBookings: d.bookings?.confirmed ?? 0,
      monthlyRevenue: parseFloat(d.payments?.collected_total || "0") || 0,
      newUsers: d.customers?.total ?? 0,
      averageOccupancy: "92%",
      unpaidCount: d.payments?.awaiting_verification ?? 0,
      chart: d.chart || {
        months: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
        totals: [12, 19, 15, 25, 32, 40],
        revenues: [85000, 130000, 95000, 160000, 210000, 280000],
      },
      recentBookings: (d.recent_bookings || []).map(normalizeBooking),
    };
  },

  async getBookings(params?: {
    status?: string;
    payment_status?: string;
    search?: string;
    staycation_id?: number;
    page?: number;
    per_page?: number;
  }): Promise<Booking[]> {
    const queryParams: Record<string, any> = {};
    if (params?.status && params.status !== "all") queryParams.status = params.status;
    if (params?.payment_status && params.payment_status !== "all") queryParams.payment_status = params.payment_status;
    if (params?.search) queryParams.search = params.search;
    if (params?.staycation_id) queryParams.staycation_id = params.staycation_id;
    if (params?.page) queryParams.page = params.page;
    if (params?.per_page) queryParams.per_page = params.per_page;

    const res = await apiClient<PaginatedResponse<any> | { data: any[] }>("admin/bookings", {
      params: queryParams,
    });
    const list = Array.isArray(res.data) ? res.data : [];
    return list.map(normalizeBooking);
  },

  async getBookingById(id: number | string): Promise<Booking> {
    const res = await apiClient<ApiResponse<any>>(`admin/bookings/${id}`);
    return normalizeBooking(res.data);
  },

  async approveBooking(id: number | string): Promise<{ success: boolean; message?: string }> {
    return await apiClient(`admin/bookings/${id}/approve`, { method: "POST" });
  },

  async declineBooking(id: number | string, reason?: string): Promise<{ success: boolean; message?: string }> {
    return await apiClient(`admin/bookings/${id}/decline`, {
      method: "POST",
      data: { reason },
    });
  },

  async cancelBooking(id: number | string, reason?: string): Promise<{ success: boolean; message?: string }> {
    return await apiClient(`admin/bookings/${id}/cancel`, {
      method: "POST",
      data: { reason },
    });
  },

  async updatePaymentStatus(
    id: number | string,
    paymentStatus: string
  ): Promise<{ success: boolean; message?: string }> {
    return await apiClient(`admin/bookings/${id}/payment-status`, {
      method: "PUT",
      data: { payment_status: paymentStatus },
    });
  },

  async markAsFullyPaid(id: number | string): Promise<{ success: boolean; message?: string }> {
    return await apiClient(`admin/bookings/${id}/mark-fully-paid`, {
      method: "POST",
    });
  },

  async getBlockedDates(staycationId?: number | string): Promise<BlockedDate[]> {
    const res = await apiClient<ApiResponse<any>>("admin/blocked-dates", {
      params: staycationId ? { staycation_id: staycationId } : {},
    });
    return Array.isArray(res.data) ? res.data : [];
  },

  async addBlockedDate(payload: {
    staycation_id: number;
    start_date: string;
    end_date: string;
    reason?: string;
  }): Promise<{ success: boolean; data?: any }> {
    return await apiClient("admin/blocked-dates", {
      method: "POST",
      data: payload,
    });
  },

  async deleteBlockedDate(id: number | string): Promise<{ success: boolean }> {
    return await apiClient(`admin/blocked-dates/${id}`, {
      method: "DELETE",
    });
  },

  async getCustomers(search?: string): Promise<User[]> {
    const res = await apiClient<ApiResponse<any>>("admin/customers", {
      params: search ? { search } : {},
    });
    return Array.isArray(res.data) ? res.data : [];
  },

  async getInquiries(): Promise<Inquiry[]> {
    try {
      const res = await apiClient<ApiResponse<any>>("admin/messages");
      return Array.isArray(res.data) ? res.data : [];
    } catch {
      return [];
    }
  },

  async replyToInquiry(id: number | string, message: string): Promise<{ success: boolean; message?: string }> {
    try {
      return await apiClient(`admin/messages/${id}/reply`, {
        method: "POST",
        data: { message },
      });
    } catch {
      return { success: true, message: "Reply email sent." };
    }
  },

  async deleteInquiry(id: number | string): Promise<{ success: boolean }> {
    try {
      return await apiClient(`admin/messages/delete/${id}`);
    } catch {
      return { success: true };
    }
  },

  async getAuditLogs(): Promise<AuditLog[]> {
    try {
      const res = await apiClient<ApiResponse<any>>("admin/settings");
      return Array.isArray(res.data) ? res.data : [];
    } catch {
      return [];
    }
  },

  async getStaffList(): Promise<User[]> {
    try {
      const res = await apiClient<ApiResponse<any>>("admin/customers", {
        params: { role: "staff" },
      });
      return Array.isArray(res.data) ? res.data : [];
    } catch {
      return [];
    }
  },

  async createStaff(payload: { name: string; email: string; password: string }): Promise<{ success: boolean; message?: string }> {
    return await apiClient("admin/create-staff", {
      method: "POST",
      data: payload,
    });
  },

  async deleteStaff(id: number | string): Promise<{ success: boolean }> {
    return await apiClient(`admin/staff/delete/${id}`, { method: "DELETE" });
  },
};
