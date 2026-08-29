"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { formatPHP, formatDate } from "@/lib/utils";
import { StatusBadge, StatCard } from "@/components/shared";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { CalendarCheck2, Search, Users, Sparkles, Phone, ArrowRight } from "lucide-react";

export default function StaffDashboardPage() {
  const [search, setSearch] = useState("");

  const { data: bookings = [], isLoading } = useQuery({
    queryKey: ["staff-bookings-dashboard", search],
    queryFn: () => adminService.getBookings({ search: search || undefined }),
  });

  return (
    <div className="space-y-8 max-w-6xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-primary block mb-1">
            Concierge Desk
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Front Desk Dashboard</h1>
        </div>

        <div className="relative w-full sm:w-72">
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search guest or reservation..."
            className="pl-8 text-xs h-9"
          />
          <Search className="h-3.5 w-3.5 text-muted-foreground absolute left-2.5 top-3" />
        </div>
      </div>

      {/* Quick Counters */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <StatCard
          title="Active Reservations"
          value={bookings.length}
          subtitle="Total recorded stays"
          icon={<CalendarCheck2 className="h-5 w-5" />}
        />
        <StatCard
          title="Today's Check-Ins"
          value={2}
          subtitle="Expected arrivals today"
          icon={<Sparkles className="h-5 w-5" />}
        />
        <StatCard
          title="Today's Check-Outs"
          value={1}
          subtitle="12:00 PM standard turnover"
          icon={<Users className="h-5 w-5" />}
        />
      </div>

      {/* Bookings Queue */}
      <div className="rounded-2xl border border-border bg-card overflow-hidden shadow-subtle">
        <div className="p-5 border-b border-border flex items-center justify-between">
          <h3 className="font-serif font-bold text-base text-foreground">
            Current Reservations & In-House Guests
          </h3>
          <Link href="/staff/bookings" className="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
            View All <ArrowRight className="h-3.5 w-3.5" />
          </Link>
        </div>

        <table className="w-full text-xs text-left">
          <thead className="bg-muted/40 border-b border-border text-muted-foreground uppercase text-[10px] tracking-wider">
            <tr>
              <th className="p-4">Guest Name</th>
              <th className="p-4">Contact</th>
              <th className="p-4">Assigned Villa</th>
              <th className="p-4">Stay Dates</th>
              <th className="p-4">Status</th>
              <th className="p-4">Payment</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border/40">
            {bookings.map((b) => (
              <tr key={b.id} className="hover:bg-muted/20 transition-colors">
                <td className="p-4 font-semibold text-foreground">{b.name}</td>
                <td className="p-4 text-muted-foreground">{b.phone}</td>
                <td className="p-4 font-medium text-foreground">{b.staycation?.house_name}</td>
                <td className="p-4 text-muted-foreground">
                  {formatDate(b.start_date)} - {formatDate(b.end_date)}
                </td>
                <td className="p-4">
                  <StatusBadge status={b.status} />
                </td>
                <td className="p-4">
                  <StatusBadge status={b.payment_status} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
