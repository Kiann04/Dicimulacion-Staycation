/**
 * The single place the frontend talks to Laravel.
 *
 * Authentication is Sanctum's SPA cookie mode, so there is no token anywhere in
 * this file and none in localStorage: the browser holds an HttpOnly session
 * cookie that JavaScript cannot read, and every request opts into sending it
 * with `credentials: "include"`.
 *
 * Nothing here decides what a user is allowed to do. Laravel's middleware and
 * policies are the only authority; this module just reports what the server
 * said so the UI can respond to it.
 */

/**
 * The Laravel origin, e.g. http://localhost:8000 - NOT the /api/v1 prefix.
 *
 * A value that still points at the versioned prefix is tolerated and reduced to
 * its origin, so an existing NEXT_PUBLIC_API_URL=.../api/v1 keeps working.
 */
function resolveBackendOrigin(): string {
  const configured = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
  return configured.replace(/\/+$/, "").replace(/\/api(\/v\d+)?$/, "");
}

export const BACKEND_URL = resolveBackendOrigin();
export const API_V1_URL = `${BACKEND_URL}/api/v1`;

/** Where an unauthenticated or expired admin session is sent. */
export const ADMIN_LOGIN_PATH = "/admin/login";

export type AuthFailureReason = "unauthenticated" | "forbidden" | "session_expired";

export class LaravelApiError extends Error {
  public status: number;
  public errors?: Record<string, string[]>;
  public errorCode?: string;

  constructor(
    message: string,
    status: number,
    options?: { errors?: Record<string, string[]>; errorCode?: string }
  ) {
    super(message);
    this.name = "LaravelApiError";
    this.status = status;
    this.errors = options?.errors;
    this.errorCode = options?.errorCode;
  }

  /**
   * 401 - no session at all.
   * 419 - the session or CSRF token expired; indistinguishable from 401 to the
   *       user, and handled the same way: sign in again.
   * 403 - the session is valid but the account may not do this. Not a login
   *       problem, so it must NOT clear auth state or bounce to the login page.
   */
  get authFailureReason(): AuthFailureReason | null {
    if (this.status === 401) return "unauthenticated";
    if (this.status === 419) return "session_expired";
    if (this.status === 403) return "forbidden";
    return null;
  }

  /** True when the fix is to sign in again. */
  get requiresLogin(): boolean {
    return this.status === 401 || this.status === 419;
  }

  get firstValidationError(): string | null {
    if (!this.errors) return null;
    const firstKey = Object.keys(this.errors)[0];
    return firstKey ? this.errors[firstKey]?.[0] ?? null : null;
  }
}

function readCookie(name: string): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(new RegExp(`(^|;\s*)${name}=([^;]*)`));
  return match ? decodeURIComponent(match[2]) : null;
}

/**
 * Primes the XSRF-TOKEN cookie. Laravel requires the matching header on every
 * stateful write, so this must run before the first POST of a session.
 *
 * The cookie is readable by design - it is the double-submit half of the CSRF
 * check. The session cookie itself stays HttpOnly and is never touched here.
 */
export async function ensureCsrfCookie(): Promise<void> {
  if (readCookie("XSRF-TOKEN")) return;

  await fetch(`${BACKEND_URL}/sanctum/csrf-cookie`, {
    method: "GET",
    credentials: "include",
    headers: { Accept: "application/json" },
  });
}

export interface LaravelRequestOptions extends Omit<RequestInit, "body"> {
  data?: unknown;
  params?: Record<string, string | number | boolean | undefined | null>;
  /** Absolute path from the Laravel root, e.g. "/login" or "/api/v1/bookings". */
  path: string;
}

export async function laravelFetch<T>(options: LaravelRequestOptions): Promise<T> {
  const { data, params, path, headers = {}, method = "GET", ...rest } = options;

  let url = `${BACKEND_URL}${path.startsWith("/") ? path : `/${path}`}`;

  if (params) {
    const query = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        query.append(key, String(value));
      }
    });
    const queryString = query.toString();
    if (queryString) url += (url.includes("?") ? "&" : "?") + queryString;
  }

  const isWrite = !["GET", "HEAD", "OPTIONS"].includes(method.toUpperCase());
  if (isWrite) await ensureCsrfCookie();

  const isFormData = typeof FormData !== "undefined" && data instanceof FormData;
  const xsrfToken = readCookie("XSRF-TOKEN");

  const requestHeaders: Record<string, string> = {
    Accept: "application/json",
    // Marks the request as AJAX so Laravel answers with JSON rather than a
    // redirect to the Blade login page.
    "X-Requested-With": "XMLHttpRequest",
    ...(isWrite && xsrfToken ? { "X-XSRF-TOKEN": xsrfToken } : {}),
    ...(data && !isFormData ? { "Content-Type": "application/json" } : {}),
    ...(headers as Record<string, string>),
  };

  let response: Response;
  try {
    response = await fetch(url, {
      ...rest,
      method,
      // The whole scheme depends on this: without it the browser sends no
      // session cookie and stores none that the server sets.
      credentials: "include",
      headers: requestHeaders,
      ...(data !== undefined
        ? { body: isFormData ? (data as FormData) : JSON.stringify(data) }
        : {}),
    });
  } catch {
    throw new LaravelApiError(
      "Unable to reach the server. Check your connection and try again.",
      0,
      { errorCode: "network_error" }
    );
  }

  const isJson = response.headers.get("content-type")?.includes("application/json");
  const body = isJson ? await response.json().catch(() => null) : null;

  if (!response.ok) {
    const fallback: Record<number, string> = {
      401: "Your session has ended. Please sign in again.",
      403: "You do not have permission to perform this action.",
      404: "The requested resource was not found.",
      419: "Your session expired. Please sign in again.",
      422: "Please check the information you entered.",
      429: "Too many attempts. Please wait a moment and try again.",
    };

    throw new LaravelApiError(
      body?.message || fallback[response.status] || "An unexpected server error occurred.",
      response.status,
      { errors: body?.errors, errorCode: body?.error_code }
    );
  }

  return body as T;
}

/** Convenience wrapper for the versioned API surface. */
export function apiV1<T>(endpoint: string, options: Omit<LaravelRequestOptions, "path"> = {}) {
  return laravelFetch<T>({
    ...options,
    path: `/api/v1/${endpoint.replace(/^\//, "")}`,
  });
}
