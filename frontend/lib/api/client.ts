// Versioned API surface for Dicimulacion Staycation.
//
// Authentication is Sanctum's SPA cookie mode and is owned by lib/api/laravel.ts;
// this module reuses that origin resolution and cookie handling rather than
// keeping a second convention. There is no bearer token and nothing is read from
// or written to localStorage here - the browser holds the HttpOnly session
// cookie and Laravel decides what the caller may see.

import { API_V1_URL, ensureCsrfCookie } from "./laravel";

const BASE_URL = API_V1_URL;

export interface DateConflict {
  type: string;
  start_date: string;
  end_date: string;
  reason: string | null;
}

export interface ApiErrorResponse {
  success?: boolean;
  message?: string;
  error_code?: string;
  errors?: Record<string, string[]>;
  conflicts?: DateConflict[];
  retry_after?: number;
}

export class ApiError extends Error {
  public status: number;
  public errorCode?: string;
  public errors?: Record<string, string[]>;
  public conflicts?: DateConflict[];
  public retryAfter?: number;
  public data?: any;

  constructor(
    message: string,
    status: number,
    options?: {
      errorCode?: string;
      errors?: Record<string, string[]>;
      conflicts?: DateConflict[];
      retryAfter?: number;
      data?: any;
    }
  ) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errorCode = options?.errorCode;
    this.errors = options?.errors;
    this.conflicts = options?.conflicts;
    this.retryAfter = options?.retryAfter;
    this.data = options?.data;
  }

  /**
   * Helper to extract the first validation error message if present.
   */
  public getFirstValidationError(): string | null {
    if (!this.errors) return null;
    const firstKey = Object.keys(this.errors)[0];
    if (firstKey && this.errors[firstKey]?.length > 0) {
      return this.errors[firstKey][0];
    }
    return null;
  }
}

export interface RequestOptions extends Omit<RequestInit, "body"> {
  data?: any;
  params?: Record<string, string | number | boolean | undefined | null>;
}

export async function apiClient<T>(endpoint: string, options: RequestOptions = {}): Promise<T> {
  const { data, params, headers = {}, ...customConfig } = options;

  let url = `${BASE_URL.replace(/\/$/, "")}/${endpoint.replace(/^\//, "")}`;

  if (params) {
    const query = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        query.append(key, String(value));
      }
    });
    const queryString = query.toString();
    if (queryString) {
      url += (url.includes("?") ? "&" : "?") + queryString;
    }
  }

  const isFormData = typeof FormData !== "undefined" && data instanceof FormData;
  const method = (customConfig.method || "GET").toUpperCase();
  const isWrite = !["GET", "HEAD", "OPTIONS"].includes(method);

  // Laravel requires the CSRF header on every stateful write.
  if (isWrite) await ensureCsrfCookie();

  const xsrfToken =
    typeof document !== "undefined"
      ? document.cookie.match(/(^|;\s*)XSRF-TOKEN=([^;]*)/)?.[2]
      : undefined;

  const defaultHeaders: Record<string, string> = {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
    ...(isWrite && xsrfToken ? { "X-XSRF-TOKEN": decodeURIComponent(xsrfToken) } : {}),
  };

  // Only set Content-Type to application/json when not using FormData (browser sets boundary automatically)
  if (!isFormData && data) {
    defaultHeaders["Content-Type"] = "application/json";
  }

  const config: RequestInit = {
    ...customConfig,
    // Sends and accepts the Laravel session cookie.
    credentials: "include",
    headers: {
      ...defaultHeaders,
      ...(headers as Record<string, string>),
    },
  };

  if (data) {
    config.body = isFormData ? data : JSON.stringify(data);
  }

  let response: Response;
  try {
    response = await fetch(url, config);
  } catch (networkErr: any) {
    throw new ApiError(
      networkErr?.message || "Network error: Unable to connect to the backend server.",
      0,
      { errorCode: "network_error" }
    );
  }

  // 401 (no session) and 419 (expired session / stale CSRF token) both mean the
  // caller has to sign in again. There is no cached identity to clear - the
  // AuthProvider owns auth state and listens for this.
  if (response.status === 401 || response.status === 419) {
    if (typeof window !== "undefined") {
      window.dispatchEvent(new CustomEvent("auth:unauthorized"));
    }
  }

  const isJson = response.headers.get("content-type")?.includes("application/json");
  const resData: ApiErrorResponse | any = isJson ? await response.json().catch(() => null) : null;

  if (!response.ok) {
    const status = response.status;
    let message = resData?.message;

    if (!message) {
      switch (status) {
        case 401:
          message = "Your session has ended. Please sign in again.";
          break;
        case 419:
          message = "Your session expired. Please sign in again.";
          break;
        case 403:
          message = "You do not have permission to perform this action.";
          break;
        case 404:
          message = "The requested resource was not found.";
          break;
        case 409:
          message = "The selected dates conflict with an existing reservation.";
          break;
        case 422:
          message = "Please check the entered information and try again.";
          break;
        case 429:
          message = "Too many requests. Please wait a moment before trying again.";
          break;
        case 500:
        default:
          message = "An unexpected server error occurred. Please try again later.";
          break;
      }
    }

    // Capture retry-after header if present
    const retryAfterHeader = response.headers.get("Retry-After");
    const retryAfter = resData?.retry_after ?? (retryAfterHeader ? parseInt(retryAfterHeader, 10) : undefined);

    throw new ApiError(message, status, {
      errorCode: resData?.error_code,
      errors: resData?.errors,
      conflicts: resData?.conflicts,
      retryAfter,
      data: resData,
    });
  }

  return resData as T;
}
