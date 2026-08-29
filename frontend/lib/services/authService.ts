import { apiClient } from "../api/client";
import { User, ApiResponse } from "../types";

export interface LoginPayload {
  email: string;
  password: string;
  device_name?: string;
  portal?: string;
}

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  device_name?: string;
}

export interface AuthSuccessData {
  user: User;
  token: string;
  token_type: string;
}

export interface UpdatePasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

function normalizeUser(rawUser: any): User {
  if (!rawUser) return rawUser;
  return {
    id: rawUser.id,
    name: rawUser.name,
    email: rawUser.email,
    role: rawUser.role || rawUser.usertype || "user",
    usertype: rawUser.role || rawUser.usertype || "user",
    email_verified: rawUser.email_verified ?? false,
    profile_photo_url: rawUser.profile_photo_url || null,
    created_at: rawUser.created_at,
  };
}

export const authService = {
  /**
   * Authenticate user with personal access token.
   */
  async login(payload: LoginPayload): Promise<{ user: User; token: string }> {
    const res = await apiClient<ApiResponse<AuthSuccessData>>("auth/login", {
      method: "POST",
      data: {
        email: payload.email,
        password: payload.password,
        device_name: payload.device_name || "web",
      },
    });

    const user = normalizeUser(res.data.user);
    const token = res.data.token;

    if (typeof window !== "undefined") {
      localStorage.setItem("token", token);
      localStorage.setItem("user", JSON.stringify(user));
      window.dispatchEvent(new CustomEvent("auth:login", { detail: { user } }));
    }

    return { user, token };
  },

  /**
   * Register a new customer account.
   */
  async register(payload: RegisterPayload): Promise<{ user: User; token: string }> {
    const res = await apiClient<ApiResponse<AuthSuccessData>>("auth/register", {
      method: "POST",
      data: {
        name: payload.name,
        email: payload.email,
        password: payload.password,
        password_confirmation: payload.password_confirmation,
        device_name: payload.device_name || "web",
      },
    });

    const user = normalizeUser(res.data.user);
    const token = res.data.token;

    if (typeof window !== "undefined") {
      localStorage.setItem("token", token);
      localStorage.setItem("user", JSON.stringify(user));
      window.dispatchEvent(new CustomEvent("auth:login", { detail: { user } }));
    }

    return { user, token };
  },

  /**
   * Verify 2FA challenge code if required.
   */
  async verifyTwoFactor(payload: { code: string; temp_token?: string }): Promise<{ user: User; token: string }> {
    const res = await apiClient<ApiResponse<AuthSuccessData>>("two-factor-challenge", {
      method: "POST",
      data: payload,
    });

    const user = normalizeUser(res.data?.user || res.data);
    const token = res.data?.token || "";

    if (typeof window !== "undefined" && token) {
      localStorage.setItem("token", token);
      localStorage.setItem("user", JSON.stringify(user));
      window.dispatchEvent(new CustomEvent("auth:login", { detail: { user } }));
    }

    return { user, token };
  },

  /**
   * Fetch current authenticated user profile from Laravel /me endpoint.
   */
  async getMe(): Promise<User | null> {
    try {
      const res = await apiClient<ApiResponse<any>>("me");
      const user = normalizeUser(res.data);
      if (typeof window !== "undefined") {
        localStorage.setItem("user", JSON.stringify(user));
      }
      return user;
    } catch {
      return null;
    }
  },

  /**
   * Terminate active session and revoke current bearer token.
   */
  async logout(): Promise<void> {
    try {
      await apiClient("auth/logout", { method: "POST" });
    } catch {
      // Proceed with local cleanup even if request fails
    } finally {
      if (typeof window !== "undefined") {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        window.dispatchEvent(new CustomEvent("auth:logout"));
      }
    }
  },

  /**
   * Revoke all personal access tokens across all devices.
   */
  async logoutAll(): Promise<void> {
    try {
      await apiClient("auth/logout-all", { method: "POST" });
    } finally {
      if (typeof window !== "undefined") {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        window.dispatchEvent(new CustomEvent("auth:logout"));
      }
    }
  },

  /**
   * Read synchronously cached user from localStorage.
   */
  getCurrentUser(): User | null {
    if (typeof window === "undefined") return null;
    const stored = localStorage.getItem("user");
    if (!stored) return null;
    try {
      return normalizeUser(JSON.parse(stored));
    } catch {
      return null;
    }
  },

  /**
   * Update name and email on user profile.
   */
  async updateProfile(payload: { name: string; email: string } | FormData): Promise<User> {
    let dataToSend: any;
    if (typeof FormData !== "undefined" && payload instanceof FormData) {
      dataToSend = {
        name: payload.get("name") as string,
        email: payload.get("email") as string,
      };
    } else {
      dataToSend = payload;
    }

    const res = await apiClient<ApiResponse<any>>("profile", {
      method: "PUT",
      data: dataToSend,
    });

    const user = normalizeUser(res.data);
    if (typeof window !== "undefined") {
      localStorage.setItem("user", JSON.stringify(user));
    }
    return user;
  },

  /**
   * Update password and revoke all other sessions.
   */
  async updatePassword(payload: UpdatePasswordPayload): Promise<{ success: boolean; message?: string }> {
    return await apiClient("profile/password", {
      method: "PUT",
      data: payload,
    });
  },
};
