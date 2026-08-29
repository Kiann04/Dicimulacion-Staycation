"use client";

import React from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { authService } from "@/lib/services/authService";
import {
  LayoutDashboard,
  CalendarCheck2,
  Users,
  MessageSquare,
  Compass,
  ArrowLeft,
  LogOut,
} from "lucide-react";

export function StaffSidebar() {
  const pathname = usePathname();
  const router = useRouter();

  const handleLogout = async () => {
    await authService.logout();
    router.push("/admin/login");
  };

  const navItems = [
    { label: "Front Desk", href: "/staff/dashboard", icon: LayoutDashboard },
    { label: "Guest Bookings", href: "/staff/bookings", icon: CalendarCheck2 },
    { label: "Customer List", href: "/staff/customers", icon: Users },
    { label: "Inquiries", href: "/staff/messages", icon: MessageSquare },
  ];

  return (
    <aside className="w-64 bg-card border-r border-border min-h-screen flex flex-col justify-between p-4 shrink-0">
      <div>
        {/* Brand */}
        <div className="flex items-center gap-3 px-3 py-3 mb-6">
          <div className="h-9 w-9 rounded-xl bg-primary flex items-center justify-center text-primary-foreground shadow-sm">
            <Compass className="h-5 w-5 text-gold-300" />
          </div>
          <div>
            <span className="font-serif text-base font-bold text-foreground block leading-tight">
              Dicimulacion
            </span>
            <span className="text-[10px] font-semibold text-primary uppercase tracking-widest block">
              Concierge Staff
            </span>
          </div>
        </div>

        {/* Navigation */}
        <nav className="space-y-1">
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = pathname === item.href || pathname.startsWith(item.href + "/");

            return (
              <Link
                key={item.label}
                href={item.href}
                className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-colors ${
                  isActive
                    ? "bg-primary text-primary-foreground shadow-sm font-semibold"
                    : "text-muted-foreground hover:bg-muted hover:text-foreground"
                }`}
              >
                <Icon className={`h-4 w-4 ${isActive ? "text-gold-300" : "text-muted-foreground"}`} />
                <span>{item.label}</span>
              </Link>
            );
          })}
        </nav>
      </div>

      <div className="pt-4 border-t border-border space-y-1">
        <Link
          href="/"
          className="flex items-center gap-3 px-3 py-2 rounded-xl text-xs text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
        >
          <ArrowLeft className="h-4 w-4" />
          <span>View Guest Site</span>
        </Link>
        <button
          onClick={handleLogout}
          className="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs text-destructive hover:bg-destructive/10 transition-colors text-left"
        >
          <LogOut className="h-4 w-4" />
          <span>Sign Out</span>
        </button>
      </div>
    </aside>
  );
}
