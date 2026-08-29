"use client";

import React, { useEffect } from "react";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth/auth-context";
import { AdminSidebar } from "@/components/admin/AdminSidebar";
import { ShieldAlert, Loader2 } from "lucide-react";

/**
 * Gates everything under /admin.
 *
 * This is a UX control, not a security boundary. It exists so the dashboard
 * does not flash before the server has said who the visitor is, and so the
 * wrong role gets a clear message instead of a screen full of failed requests.
 * The data itself is protected by Laravel's admin middleware and policies, and
 * would stay protected even if this component were deleted.
 *
 * Children are never rendered until the server has answered, so no protected
 * markup reaches the DOM for an unauthenticated visitor. The admin chrome lives
 * here too: /admin/login sits under the same layout and must render without a
 * sidebar, and the sidebar itself is only meaningful once a role is confirmed.
 */

function AdminShell({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen bg-background text-foreground">
      <div className="hidden md:block">
        <AdminSidebar />
      </div>
      <div className="flex-1 flex flex-col min-w-0 overflow-x-hidden">
        <main className="flex-1 p-6 sm:p-8 lg:p-10">{children}</main>
      </div>
    </div>
  );
}
export function AdminGuard({ children }: { children: React.ReactNode }) {
  const { status, user } = useAuth();
  const router = useRouter();
  const pathname = usePathname();

  const isLoginPage = pathname === "/admin/login";
  const isAdmin = status === "authenticated" && user?.role === "admin";

  useEffect(() => {
    if (status === "loading" || isLoginPage) return;

    if (status === "unauthenticated") {
      router.replace("/admin/login");
    }
  }, [status, isLoginPage, router]);

  // The login page is inside /admin but must render for signed-out visitors.
  if (isLoginPage) {
    return <>{children}</>;
  }

  if (status === "loading") {
    return (
      <div className="flex min-h-[60vh] items-center justify-center" role="status" aria-live="polite">
        <div className="flex flex-col items-center gap-3 text-muted-foreground">
          <Loader2 className="h-6 w-6 animate-spin" aria-hidden="true" />
          <p className="text-xs font-medium uppercase tracking-widest">Verifying session</p>
        </div>
      </div>
    );
  }

  // The redirect is already queued; rendering nothing avoids showing the
  // dashboard for the frame before navigation happens.
  if (status === "unauthenticated") {
    return null;
  }

  if (!isAdmin) {
    return (
      <div className="flex min-h-[60vh] items-center justify-center px-6">
        <div className="w-full max-w-md rounded-2xl border border-border bg-card p-8 text-center shadow-card">
          <div className="mx-auto mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-destructive/10 text-destructive">
            <ShieldAlert className="h-6 w-6" aria-hidden="true" />
          </div>
          <h1 className="font-serif text-xl font-bold text-foreground">Administrator access required</h1>
          <p className="mt-2 text-xs text-muted-foreground">
            You are signed in as <span className="font-semibold text-foreground">{user?.email}</span>
            {user?.role ? ` (${user.role})` : ""}, which cannot open the admin portal.
          </p>
          <div className="mt-6 flex flex-col gap-2">
            {user?.role === "staff" && (
              <button
                onClick={() => router.replace("/staff/dashboard")}
                className="w-full rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-primary-foreground transition-colors hover:opacity-90"
              >
                Go to the staff dashboard
              </button>
            )}
            <button
              onClick={() => router.replace("/admin/login")}
              className="w-full rounded-xl border border-border px-4 py-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-muted"
            >
              Sign in as an administrator
            </button>
          </div>
        </div>
      </div>
    );
  }

  return <AdminShell>{children}</AdminShell>;
}
