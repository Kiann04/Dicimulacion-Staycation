"use client";

import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { LaravelApiError, ensureCsrfCookie, laravelFetch } from "@/lib/api/laravel";
import type { User } from "@/lib/types";

/**
 * Auth state is deliberately minimal and lives in memory only.
 *
 * Nothing is mirrored into localStorage: a cached "user" blob is trivially
 * editable from the console, so treating it as identity is the bug this
 * provider exists to remove. The session cookie is the only persistence, the
 * browser owns it, and /api/auth/me is the only thing that can say who someone
 * is. On refresh the answer is fetched again rather than remembered.
 */
export type AuthStatus = "loading" | "authenticated" | "unauthenticated";

interface AuthContextValue {
  status: AuthStatus;
  user: User | null;
  /** True only once the server has actually answered. */
  isResolved: boolean;
  login: (email: string, password: string, portal?: "guest" | "admin") => Promise<User>;
  logout: () => Promise<void>;
  refresh: () => Promise<User | null>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

interface MeResponse {
  success: boolean;
  data: User;
}

interface LoginResponse {
  success: boolean;
  data: { user?: User; two_factor_required?: boolean };
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [status, setStatus] = useState<AuthStatus>("loading");
  const [user, setUser] = useState<User | null>(null);

  /**
   * Asks Laravel who the caller is. A 401/419 is an ordinary answer here
   * ("nobody"), not an error to surface - it is how a signed-out visitor and an
   * expired session both look.
   */
  const refresh = useCallback(async (): Promise<User | null> => {
    try {
      const response = await laravelFetch<MeResponse>({ path: "/api/auth/me" });
      const nextUser = response.data ?? null;
      setUser(nextUser);
      setStatus(nextUser ? "authenticated" : "unauthenticated");
      return nextUser;
    } catch (error) {
      if (error instanceof LaravelApiError && error.status === 403) {
        // Authenticated, but not permitted to read this. Keep whatever the
        // server previously confirmed rather than pretending to be signed out.
        setStatus(user ? "authenticated" : "unauthenticated");
        return user;
      }

      setUser(null);
      setStatus("unauthenticated");
      return null;
    }
  }, [user]);

  useEffect(() => {
    let active = true;

    (async () => {
      try {
        const response = await laravelFetch<MeResponse>({ path: "/api/auth/me" });
        if (!active) return;
        setUser(response.data ?? null);
        setStatus(response.data ? "authenticated" : "unauthenticated");
      } catch {
        if (!active) return;
        setUser(null);
        setStatus("unauthenticated");
      }
    })();

    return () => {
      active = false;
    };
  }, []);

  const login = useCallback(async (email: string, password: string, portal: "guest" | "admin" = "guest") => {
    // Sanctum's cookie flow: prime the CSRF cookie, then post credentials to
    // the session route. No token is returned and none is stored.
    await ensureCsrfCookie();

    const response = await laravelFetch<LoginResponse>({
      path: portal === "admin" ? "/admin/login" : "/login",
      method: "POST",
      data: { email, password },
    });

    if (response.data?.two_factor_required) {
      throw new LaravelApiError(
        "This account requires two-factor authentication. Please complete the challenge to continue.",
        200,
        { errorCode: "two_factor_required" }
      );
    }

    /*
     * The login response is not trusted as the source of identity. The role
     * that gates the dashboard is re-read from /api/auth/me against the session
     * cookie the server just issued, so one endpoint decides identity.
     */
    const confirmed = await laravelFetch<MeResponse>({ path: "/api/auth/me" });
    const nextUser = confirmed.data ?? null;

    if (!nextUser) {
      throw new LaravelApiError("Sign in did not establish a session.", 401);
    }

    setUser(nextUser);
    setStatus("authenticated");
    return nextUser;
  }, []);

  const logout = useCallback(async () => {
    try {
      await laravelFetch({ path: "/logout", method: "POST" });
    } catch {
      // The session may already be gone server-side. Clearing local state is
      // still correct, so a failure here must not strand the user signed in.
    } finally {
      setUser(null);
      setStatus("unauthenticated");
    }
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      status,
      user,
      isResolved: status !== "loading",
      login,
      logout,
      refresh,
    }),
    [status, user, login, logout, refresh]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider.");
  }
  return context;
}

/** Convenience read. Never treat this as authorization - Laravel decides. */
export function useIsAdmin(): boolean {
  const { user, status } = useAuth();
  return status === "authenticated" && user?.role === "admin";
}
