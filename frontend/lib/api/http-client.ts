import type { ApiErrorResponse } from './types.ts';

export class ApiClientError extends Error {
  status: number;
  errors?: Record<string, string[]>;
  rawData?: unknown;

  constructor(
    status: number,
    message: string,
    errors?: Record<string, string[]>,
    rawData?: unknown
  ) {
    super(message);
    this.name = 'ApiClientError';
    this.status = status;
    this.errors = errors;
    this.rawData = rawData;
  }

  get isNotFound(): boolean {
    return this.status === 404;
  }

  get isRateLimited(): boolean {
    return this.status === 429;
  }

  get isValidationError(): boolean {
    return this.status === 422;
  }

  get isServerError(): boolean {
    return this.status >= 500;
  }
}

/**
 * Validates and returns the configured API base URL without trailing slash.
 * In production, NEXT_PUBLIC_API_BASE_URL is strictly required.
 * In development, defaults to http://localhost:8000.
 */
export function getApiBaseUrl(): string {
  const envUrl = process.env.NEXT_PUBLIC_API_BASE_URL;
  const isProduction = process.env.NODE_ENV === 'production';

  if (!envUrl || !envUrl.trim()) {
    if (isProduction) {
      throw new ApiClientError(
        500,
        'NEXT_PUBLIC_API_BASE_URL is not configured for production environment.'
      );
    }
    return 'http://localhost:8000';
  }

  const trimmed = envUrl.trim().replace(/\/+$/, '');

  try {
    const parsed = new URL(trimmed);
    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
      throw new Error('Unsupported protocol');
    }
  } catch {
    throw new ApiClientError(
      500,
      `Invalid NEXT_PUBLIC_API_BASE_URL "${trimmed}": must be a valid http or https URL.`
    );
  }

  return trimmed;
}

export interface ApiFetchOptions extends RequestInit {
  timeoutMs?: number;
}

/**
 * Core JSON fetch wrapper for Laravel /api/v1 endpoints with strict error sanitization.
 */
export async function apiFetch<T>(
  path: string,
  options: ApiFetchOptions = {}
): Promise<T> {
  const baseUrl = getApiBaseUrl();
  const cleanPath = path.startsWith('/') ? path : `/${path}`;
  const url = `${baseUrl}${cleanPath}`;

  const { timeoutMs = 15000, headers, ...restOptions } = options;

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

  const requestHeaders = new Headers(headers);
  if (!requestHeaders.has('Accept')) {
    requestHeaders.set('Accept', 'application/json');
  }

  try {
    const response = await fetch(url, {
      ...restOptions,
      headers: requestHeaders,
      signal: controller.signal,
    });

    const isJson = response.headers.get('content-type')?.includes('application/json');
    let body: unknown = null;

    if (isJson) {
      try {
        body = await response.json();
      } catch {
        body = null;
      }
    }

    if (!response.ok) {
      let errorMessage = 'An unexpected API error occurred.';
      let validationErrors: Record<string, string[]> | undefined;

      if (body && typeof body === 'object') {
        const errorBody = body as ApiErrorResponse;
        if (errorBody.message) {
          errorMessage = errorBody.message;
        }
        if (errorBody.errors) {
          validationErrors = errorBody.errors;
        }
      }

      // Customer-friendly messages based on HTTP status
      if (response.status === 429) {
        errorMessage = 'Too many requests. Please wait a moment and try again.';
      } else if (response.status === 404) {
        errorMessage = errorMessage === 'An unexpected API error occurred.' ? 'Staycation not found.' : errorMessage;
      } else if (response.status >= 500) {
        errorMessage = 'A server error occurred. Please try again later.';
      }

      throw new ApiClientError(
        response.status,
        errorMessage,
        validationErrors,
        body
      );
    }

    // Success response validation: must be valid JSON object or array
    if (!isJson || body === null || typeof body !== 'object') {
      throw new ApiClientError(
        502,
        'Invalid API response envelope received from server.'
      );
    }

    return body as T;
  } catch (error) {
    if (error instanceof ApiClientError) {
      throw error;
    }

    if (error instanceof DOMException && error.name === 'AbortError') {
      throw new ApiClientError(
        408,
        'The request timed out. Please check your connection and try again.'
      );
    }

    // Sanitize raw network errors (e.g. "fetch failed", "ECONNREFUSED")
    throw new ApiClientError(
      503,
      'Unable to connect to the staycation service. Please verify the API is reachable.'
    );
  } finally {
    clearTimeout(timeoutId);
  }
}
