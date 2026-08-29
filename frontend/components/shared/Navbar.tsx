"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth/auth-context";
import { Button } from "@/components/ui/button";
import {
  Compass,
  Calendar,
  User as UserIcon,
  LogOut,
  ShieldCheck,
  Menu,
  X,
  Sparkles,
  PhoneCall,
  Home,
  MessageSquare,
} from "lucide-react";

export function Navbar() {
  const pathname = usePathname();
  const router = useRouter();
  // The session is resolved once, by the AuthProvider, from /api/auth/me.
  const { user, logout } = useAuth();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, [pathname]);

  const handleLogout = async () => {
    await logout();
    router.push("/");
  };

  const navLinks = [
    { label: "Home", href: "/" },
    { label: "Villas & Suites", href: "/#villas" },
    { label: "Amenities", href: "/#amenities" },
    { label: "Guest Reviews", href: "/#reviews" },
    { label: "Contact Us", href: "/#contact" },
  ];

  return (
    <header
      className={`sticky top-0 z-40 w-full transition-all duration-300 ${
        isScrolled
          ? "glass-nav py-3 shadow-subtle"
          : "bg-background/90 backdrop-blur-md py-4 border-b border-border/40"
      }`}
    >
      <div className="container mx-auto flex items-center justify-between">
        {/* Brand Logo */}
        <Link href="/" className="flex items-center gap-2.5 group">
          <div className="h-10 w-10 rounded-xl bg-primary flex items-center justify-center text-primary-foreground shadow-sm transition-transform duration-300 group-hover:scale-105">
            <Compass className="h-5 w-5 text-gold-300" />
          </div>
          <div>
            <span className="font-serif text-lg font-bold tracking-tight text-foreground block leading-none">
              Dicimulacion
            </span>
            <span className="text-[10px] font-semibold tracking-widest text-accent uppercase block mt-0.5">
              Staycation Retreats
            </span>
          </div>
        </Link>

        {/* Desktop Nav Links */}
        <nav className="hidden md:flex items-center gap-7">
          {navLinks.map((link) => (
            <Link
              key={link.label}
              href={link.href}
              className="text-sm font-medium text-muted-foreground hover:text-primary transition-colors"
            >
              {link.label}
            </Link>
          ))}
        </nav>

        {/* Desktop Action Buttons / User Menu */}
        <div className="hidden md:flex items-center gap-3.5">
          {user ? (
            <div className="flex items-center gap-3">
              <Link href="/bookings">
                <Button variant="outline" size="sm" className="gap-2 rounded-lg font-medium">
                  <Calendar className="h-4 w-4 text-primary" />
                  My Bookings
                </Button>
              </Link>

              <div className="flex items-center gap-2 pl-2 border-l border-border">
                <Link
                  href="/profile"
                  className="flex items-center gap-2 rounded-lg py-1 px-2 hover:bg-muted transition-colors text-sm font-medium text-foreground"
                >
                  <div className="h-7 w-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-semibold text-xs overflow-hidden border border-primary/20">
                    {user.profile_photo_url ? (
                      <img src={user.profile_photo_url} alt={user.name} className="h-full w-full object-cover" />
                    ) : (
                      user.name.charAt(0).toUpperCase()
                    )}
                  </div>
                  <span className="max-w-[100px] truncate">{user.name.split(" ")[0]}</span>
                </Link>

                {user.usertype === "admin" && (
                  <Link href="/admin/dashboard">
                    <Button variant="secondary" size="sm" className="gap-1.5 text-xs">
                      <ShieldCheck className="h-3.5 w-3.5 text-emerald-600" />
                      Admin
                    </Button>
                  </Link>
                )}

                {user.usertype === "staff" && (
                  <Link href="/staff/dashboard">
                    <Button variant="secondary" size="sm" className="gap-1.5 text-xs">
                      <ShieldCheck className="h-3.5 w-3.5 text-primary" />
                      Staff
                    </Button>
                  </Link>
                )}

                <button
                  onClick={handleLogout}
                  className="p-1.5 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors"
                  title="Logout"
                >
                  <LogOut className="h-4 w-4" />
                </button>
              </div>
            </div>
          ) : (
            <div className="flex items-center gap-2.5">
              <Link href="/login">
                <Button variant="ghost" size="sm" className="font-medium text-sm">
                  Sign In
                </Button>
              </Link>
              <Link href="/register">
                <Button variant="gold" size="sm" className="rounded-lg shadow-sm">
                  Book Stay
                </Button>
              </Link>
            </div>
          )}
        </div>

        {/* Mobile Menu Button */}
        <div className="md:hidden flex items-center gap-2">
          <Link href="/#villas">
            <Button variant="gold" size="sm" className="text-xs px-3">
              Book
            </Button>
          </Link>
          <button
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="p-2 rounded-lg text-foreground hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            aria-label="Toggle menu"
          >
            {isMobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
          </button>
        </div>
      </div>

      {/* Mobile Drawer */}
      {isMobileMenuOpen && (
        <div className="md:hidden border-b border-border bg-background/95 backdrop-blur-xl px-6 py-5 animate-in slide-in-from-top-4 duration-200">
          <nav className="flex flex-col gap-3.5">
            {navLinks.map((link) => (
              <Link
                key={link.label}
                href={link.href}
                onClick={() => setIsMobileMenuOpen(false)}
                className="text-base font-medium text-foreground hover:text-primary py-1"
              >
                {link.label}
              </Link>
            ))}

            <div className="pt-4 border-t border-border flex flex-col gap-2.5">
              {user ? (
                <>
                  <div className="flex items-center gap-3 py-2">
                    <div className="h-8 w-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center font-semibold text-xs">
                      {user.name.charAt(0)}
                    </div>
                    <div>
                      <p className="text-sm font-semibold text-foreground">{user.name}</p>
                      <p className="text-xs text-muted-foreground">{user.email}</p>
                    </div>
                  </div>
                  <Link href="/bookings" onClick={() => setIsMobileMenuOpen(false)}>
                    <Button variant="outline" className="w-full justify-start gap-2">
                      <Calendar className="h-4 w-4" />
                      My Bookings
                    </Button>
                  </Link>
                  <Link href="/profile" onClick={() => setIsMobileMenuOpen(false)}>
                    <Button variant="outline" className="w-full justify-start gap-2">
                      <UserIcon className="h-4 w-4" />
                      Profile Settings
                    </Button>
                  </Link>
                  {user.usertype === "admin" && (
                    <Link href="/admin/dashboard" onClick={() => setIsMobileMenuOpen(false)}>
                      <Button variant="secondary" className="w-full justify-start gap-2 text-emerald-700">
                        <ShieldCheck className="h-4 w-4" />
                        Admin Dashboard
                      </Button>
                    </Link>
                  )}
                  <Button variant="destructive" onClick={handleLogout} className="w-full justify-start gap-2 mt-1">
                    <LogOut className="h-4 w-4" />
                    Sign Out
                  </Button>
                </>
              ) : (
                <div className="grid grid-cols-2 gap-3 pt-2">
                  <Link href="/login" onClick={() => setIsMobileMenuOpen(false)}>
                    <Button variant="outline" className="w-full">
                      Sign In
                    </Button>
                  </Link>
                  <Link href="/register" onClick={() => setIsMobileMenuOpen(false)}>
                    <Button variant="gold" className="w-full">
                      Register
                    </Button>
                  </Link>
                </div>
              )}
            </div>
          </nav>
        </div>
      )}
    </header>
  );
}
