"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { authService } from "@/lib/services/authService";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Compass, Lock, Mail, User, ShieldCheck } from "lucide-react";

export default function RegisterPage() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    if (password !== passwordConfirmation) {
      setError("Passwords do not match.");
      return;
    }
    setIsLoading(true);
    setError("");

    try {
      await authService.register({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      router.push("/");
    } catch (err: any) {
      setError(err?.message || "Registration failed. Please try again.");
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
          <h2 className="font-serif text-2xl font-bold text-foreground mt-4">Create an Account</h2>
          <p className="text-xs text-muted-foreground mt-1">
            Book private staycation retreats and manage reservations seamlessly
          </p>
        </div>

        {/* Card */}
        <div className="rounded-2xl border border-border/80 bg-card p-6 sm:p-8 shadow-card">
          {error && (
            <div className="p-3 mb-5 rounded-xl bg-destructive/10 border border-destructive/30 text-destructive text-xs">
              {error}
            </div>
          )}

          <form onSubmit={handleRegister} className="space-y-4 text-xs sm:text-sm">
            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Full Name
              </label>
              <div className="relative">
                <Input
                  required
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  placeholder="Juan Dela Cruz"
                  className="pl-9"
                />
                <User className="h-4 w-4 text-muted-foreground absolute left-3 top-3.5" />
              </div>
            </div>

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
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Password
              </label>
              <div className="relative">
                <Input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="At least 8 characters"
                  className="pl-9"
                />
                <Lock className="h-4 w-4 text-muted-foreground absolute left-3 top-3.5" />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-muted-foreground uppercase mb-1">
                Confirm Password
              </label>
              <div className="relative">
                <Input
                  type="password"
                  required
                  value={passwordConfirmation}
                  onChange={(e) => setPasswordConfirmation(e.target.value)}
                  placeholder="Re-enter your password"
                  className="pl-9"
                />
                <Lock className="h-4 w-4 text-muted-foreground absolute left-3 top-3.5" />
              </div>
            </div>

            <Button type="submit" variant="gold" size="lg" isLoading={isLoading} className="w-full font-bold mt-2 shadow-md">
              Create Account
            </Button>
          </form>

          <div className="mt-6 pt-5 border-t border-border/60 text-center text-xs text-muted-foreground">
            Already have an account?{" "}
            <Link href="/login" className="font-semibold text-primary hover:underline">
              Sign In
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
