"use client";

import React, { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { adminService } from "@/lib/services/adminService";
import { formatPHP, formatDate } from "@/lib/utils";
import { StatusBadge, EmptyState } from "@/components/shared";
import { Input } from "@/components/ui/input";
import { CalendarCheck2, Search, Users, Phone, MapPin } from "lucide-react";

export default function StaffBookingsPage() {
  const [search, setSearch] = useState("");

  const { data: bookings = [], isLoading } = useQuery({
    queryKey: ["staff-bookings", search],
    queryFn: () => adminService.getBookings({ search: search || undefined }),
  });

  return (
    <div className="space-y-8 max-w-6xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-xs font-bold uppercase tracking-widest text-primary block mb-1">
            Reservations
          </span>
          <h1 className="font-serif text-3xl font-bold text-foreground">Guest Stay Bookings</h1>
        </div>

        <div className="relative w-full sm:w-72">
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search by guest, villa, or phone..."
            className="pl-8 text-xs h-9"
          />
          <Search className="h-3.5 w-3.5 text-muted-foreground absolute left-2.5 top-3" />
        </div>
      </div>

      {isLoading ? (
        <div className="space-y-3 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-16 rounded-xl bg-muted" />
          ))}
        </div>
      ) : bookings.length === 0 ? (
        <EmptyState
          icon={<CalendarCheck2 className="h-8 w-8" />}
          title="No Bookings Found"
          description="There are no bookings matching your search."
        />
      ) : (
        <div className="rounded-2xl border border-border bg-card overflow-hidden shadow-subtle">
          <table className="w-full text-xs text-left">
            <thead className="bg-muted/40 border-b border-border text-muted-foreground uppercase text-[10px] tracking-wider">
              <tr>
                <th className="p-4">Reservation #</th>
                <th className="p-4">Guest Information</th>
                <th className="p-4">Villa Property</th>
                <th className="p-4">Stay Dates</th>
                <th className="p-4">Status</th>
                <th className="p-4">Payment</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border/40">
              {bookings.map((b) => (
                <tr key={b.id} className="hover:bg-muted/20 transition-colors">
                  <td className="p-4 font-mono font-bold text-muted-foreground">#{b.id}</td>
                  <td className="p-4">
                    <div className="font-semibold text-foreground">{b.name}</div>
                    <div className="text-[11px] text-muted-foreground">{b.phone}</div>
                  </td>
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
      )}
    </div>
  );
}
