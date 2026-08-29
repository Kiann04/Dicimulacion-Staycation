// Centralized API client for Dicimulacion Staycation (Laravel v1 REST API)

const BASE_URL = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000/api/v1";

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

  const token = typeof window !== "undefined" ? localStorage.getItem("token") : null;
  const isFormData = typeof FormData !== "undefined" && data instanceof FormData;

  const defaultHeaders: Record<string, string> = {
    Accept: "application/json",
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };

  // Only set Content-Type to application/json when not using FormData (browser sets boundary automatically)
  if (!isFormData && data) {
    defaultHeaders["Content-Type"] = "application/json";
  }

  const config: RequestInit = {
    ...customConfig,
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

  // Handle 401 Unauthorized (token invalid / revoked / expired)
  if (response.status === 401) {
    if (typeof window !== "undefined") {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
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
          message = "Your session has expired. Please sign in again.";
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
