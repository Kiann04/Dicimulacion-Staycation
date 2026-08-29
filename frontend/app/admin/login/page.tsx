"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth/auth-context";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Shield, Lock, Mail, Compass, KeyRound } from "lucide-react";

export default function AdminStaffLoginPage() {
  const router = useRouter();
  const { login, status, user } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  // Someone who already holds a session should not sit on the login form.
  useEffect(() => {
    if (status !== "authenticated" || !user) return;
    if (user.role === "admin") router.replace("/admin/dashboard");
    else if (user.role === "staff") router.replace("/staff/dashboard");
  }, [status, user, router]);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError("");

    try {
      /*
       * The role used for routing comes from the session Laravel just issued
       * (via /api/auth/me inside login), never from anything this form holds.
       * Sending someone to /admin/dashboard grants nothing on its own - the
       * guard re-checks, and every request behind it is authorised server-side.
       */
      const authenticated = await login(email, password, "admin");

      if (authenticated.role === "admin") {
        router.replace("/admin/dashboard");
      } else if (authenticated.role === "staff") {
        router.replace("/staff/dashboard");
      } else {
        setError("Only Admin and Staff accounts are authorized to enter here.");
      }
    } catch (err: any) {
      setError(err?.message || "Invalid administrative credentials.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-[85vh] flex items-center justify-center py-12 px-6 bg-background">
      <div className="w-full max-w-md">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="inline-flex h-12 w-12 rounded-2xl bg-primary text-primary-foreground items-center justify-center shadow-md mb-3">
            <Shield className="h-6 w-6 text-gold-300" />
          </div>
          <h2 className="font-serif text-2xl font-bold text-foreground">Management Portal</h2>
          <p className="text-xs text-muted-foreground mt-1">
            Sign in with Administrator or Concierge Staff credentials
          </p>
        </div>

        {/* Card */}
        <div className="rounded-2xl border border-border/80 bg-card p-6 sm:p-8 shadow-card">
          {error && (
            <div className="p-3 mb-5 rounded-xl bg-destructive/10 border border-destructive/30 text-destructive text-xs">
              {error}
            </div>
          )}

          <form onSubmit={handleLogin} className="space-y-4 text-xs sm:text-sm">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Authorized Email Address
              </label>
              <div className="relative">
                <Input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="staff@dicimulacionstaycation.com"
                  className="pl-9"
                />
                <Mail className="h-4 w-4 text-muted-foreground absolute left-3 top-3.5" />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Password
              </label>
              <div className="relative">
                <Input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="pl-9"
                />
                <Lock className="h-4 w-4 text-muted-foreground absolute left-3 top-3.5" />
              </div>
            </div>

            <Button type="submit" variant="default" size="lg" isLoading={isLoading} className="w-full font-bold mt-2">
              Authenticate & Access Portal
            </Button>
          </form>

          <div className="mt-6 pt-5 border-t border-border/60 text-center text-xs text-muted-foreground">
            Looking for customer bookings?{" "}
            <Link href="/login" className="font-semibold text-primary hover:underline">
              Guest Sign In
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
