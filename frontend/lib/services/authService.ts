import { apiClient } from "../api/client";
import { ensureCsrfCookie, laravelFetch } from "../api/laravel";
import { User, ApiResponse } from "../types";

/**
 * Session-backed auth operations.
 *
 * Sanctum's SPA cookie mode means there is no token to hand around and nothing
 * to cache: the browser holds an HttpOnly session cookie it will not let script
 * read, and GET /api/auth/me is the only way to learn who the caller is.
 *
 * The previous implementation stored the user and a bearer token in
 * localStorage and read `role` back out of it to decide what to show. That made
 * "am I an admin?" a client-side value anyone could edit from the console, so
 * it has been removed rather than adjusted. React state (see lib/auth/auth-context)
 * is the only place a resolved user now lives.
 */

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface UpdatePasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

interface MeResponse {
  success: boolean;
  data: User;
}

export const authService = {
  /**
   * Opens a Laravel session. `portal: "admin"` posts to /admin/login, which
   * refuses anyone who is not admin or staff; the guest portal uses /login.
   */
  async login(payload: { email: string; password: string; portal?: "guest" | "admin" }): Promise<User> {
    await ensureCsrfCookie();

    await laravelFetch({
      path: payload.portal === "admin" ? "/admin/login" : "/login",
      method: "POST",
      data: { email: payload.email, password: payload.password },
    });

    return await this.getMe();
  },

  /** Registers a customer, then reads the resulting session back from the server. */
  async register(payload: RegisterPayload): Promise<User> {
    await ensureCsrfCookie();

    await laravelFetch({
      path: "/register",
      method: "POST",
      data: payload,
    });

    return await this.getMe();
  },

  /**
   * Completes Fortify's two-factor challenge against the pending session, then
   * reads the resulting user back from the server.
   */
  async verifyTwoFactor(payload: { code?: string; recovery_code?: string }): Promise<User> {
    await ensureCsrfCookie();

    await laravelFetch({
      path: "/two-factor-challenge",
      method: "POST",
      data: payload.recovery_code
        ? { recovery_code: payload.recovery_code }
        : { code: payload.code },
    });

    return await this.getMe();
  },

  /**
   * The single source of identity. Throws on 401/419 so callers cannot mistake
   * "no session" for "some cached user".
   */
  async getMe(): Promise<User> {
    const response = await laravelFetch<MeResponse>({ path: "/api/auth/me" });
    return response.data;
  },

  /** Ends the session server-side. Local state is cleared by the AuthProvider. */
  async logout(): Promise<void> {
    try {
      await laravelFetch({ path: "/logout", method: "POST" });
    } catch {
      // Already signed out, or the session expired. Either way the caller
      // proceeds to clear its own state.
    }
  },

  async updateProfile(payload: { name: string; email: string } | FormData): Promise<User> {
    const body =
      typeof FormData !== "undefined" && payload instanceof FormData
        ? { name: String(payload.get("name") ?? ""), email: String(payload.get("email") ?? "") }
        : payload;

    const res = await apiClient<ApiResponse<User>>("profile", {
      method: "PUT",
      data: body,
    });
    return res.data;
  },

  async updatePassword(payload: UpdatePasswordPayload): Promise<{ success: boolean; message?: string }> {
    return await apiClient("profile/password", {
      method: "PUT",
      data: payload,
    });
  },
};
