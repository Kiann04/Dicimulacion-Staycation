"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { authService } from "@/lib/services/authService";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Compass, Lock, Mail, ArrowRight, ShieldCheck } from "lucide-react";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError("");

    try {
      await authService.login({
        email,
        password,
        portal: "user",
      });

      router.push("/");
    } catch (err: any) {
      setError(err?.message || "Invalid email or password.");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-[85vh] flex items-center justify-center py-12 px-6 bg-background">
      <div className="w-full max-w-md">
        {/* Logo */}
        <div className="text-center mb-8">
          <Link href="/" className="inline-flex items-center gap-2.5 group">
            <div className="h-10 w-10 rounded-xl bg-primary flex items-center justify-center text-primary-foreground shadow-sm">
              <Compass className="h-5 w-5 text-gold-300" />
            </div>
            <span className="font-serif text-2xl font-bold tracking-tight text-foreground">
              Dicimulacion
            </span>
          </Link>
          <h2 className="font-serif text-2xl font-bold text-foreground mt-4">Welcome Back</h2>
          <p className="text-xs text-muted-foreground mt-1">
            Sign in to manage your staycation bookings & reservations
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
                Email Address
              </label>
              <div className="relative">
                <Input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="juan@example.com"
                  className="pl-9"
                />
                <Mail className="h-4 w-4 text-muted-foreground absolute left-3 top-3.5" />
              </div>
            </div>

            <div>
              <div className="flex items-center justify-between mb-1">
                <label className="text-xs font-semibold text-muted-foreground uppercase">
                  Password
                </label>
              </div>
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

            <Button type="submit" variant="gold" size="lg" isLoading={isLoading} className="w-full font-bold mt-2 shadow-md">
              Sign In to Account
            </Button>
          </form>

          <div className="mt-6 pt-5 border-t border-border/60 text-center text-xs text-muted-foreground">
            Don&apos;t have an account yet?{" "}
            <Link href="/register" className="font-semibold text-primary hover:underline">
              Create an Account
            </Link>
          </div>
        </div>

        {/* Admin Login Link */}
        <div className="text-center mt-6">
          <Link
            href="/admin/login"
            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors"
          >
            <ShieldCheck className="h-3.5 w-3.5 text-accent" />
            <span>Staff & Administrator Portal Login</span>
          </Link>
        </div>
      </div>
    </div>
  );
}
