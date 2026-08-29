"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { bookingService } from "@/lib/services/bookingService";
import { Booking } from "@/lib/types";
import { formatPHP, formatDate } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { StatusBadge, EmptyState } from "@/components/shared";
import { Dialog } from "@/components/ui/dialog";
import {
  Calendar,
  MapPin,
  Users,
  CreditCard,
  XCircle,
  Clock,
  ShieldCheck,
  AlertCircle,
  ExternalLink,
} from "lucide-react";

export default function BookingHistoryPage() {
  const queryClient = useQueryClient();
  const [cancelError, setCancelError] = useState<string | null>(null);

  const {
    data: bookings = [],
    isLoading,
    isError,
    error,
    refetch,
  } = useQuery({
    queryKey: ["customer-bookings"],
    queryFn: () => bookingService.getHistory(),
  });

  // Cancel Booking Mutation
  const cancelMutation = useMutation({
    mutationFn: (id: number) => bookingService.cancel(id),
    onSuccess: () => {
      setCancelError(null);
      queryClient.invalidateQueries({ queryKey: ["customer-bookings"] });
    },
    onError: (err: any) => {
      setCancelError(err?.message || "Failed to cancel booking reservation.");
    },
  });

  const handleCancelClick = (booking: Booking) => {
    if (confirm(`Are you sure you want to cancel your reservation for ${booking.staycation?.name || booking.staycation?.house_name || "this staycation"}?`)) {
      cancelMutation.mutate(booking.id);
    }
  };

  return (
    <div className="min-h-screen py-12 bg-background">
      <div className="container mx-auto px-6 max-w-5xl">
        {/* Page Header */}
        <div className="mb-10">
          <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-1">
            My Account
          </span>
          <h1 className="font-serif text-3xl sm:text-4xl font-bold text-foreground">
            Booking Reservations & History
          </h1>
          <p className="text-sm text-muted-foreground mt-2">
            Track your stay reservation confirmations, downpayment statuses, and manage your booked dates.
          </p>
        </div>

        {cancelError && (
          <div className="mb-6 p-4 rounded-xl bg-destructive/10 border border-destructive/30 text-destructive text-xs flex items-center justify-between">
            <span>{cancelError}</span>
            <Button variant="ghost" size="sm" onClick={() => setCancelError(null)} className="h-6 px-2 text-xs">
              Dismiss
            </Button>
          </div>
        )}

        {/* Bookings List */}
        {isLoading ? (
          <div className="space-y-6">
            {[1, 2].map((i) => (
              <div key={i} className="h-48 rounded-2xl bg-muted/60 animate-pulse" />
            ))}
          </div>
        ) : isError ? (
          <div className="p-8 rounded-2xl border border-destructive/30 bg-destructive/5 text-center space-y-4">
            <AlertCircle className="h-10 w-10 text-destructive mx-auto" />
            <h3 className="font-serif text-lg font-bold text-foreground">Unable to Load Bookings</h3>
            <p className="text-xs text-muted-foreground max-w-md mx-auto">
              {(error as any)?.message || "Failed to connect to the reservation service. Please check your connection."}
            </p>
            <Button onClick={() => refetch()} variant="outline" size="sm" className="gap-2">
              Try Again
            </Button>
          </div>
        ) : bookings.length === 0 ? (
          <EmptyState
            icon={<Calendar className="h-8 w-8" />}
            title="No Bookings Yet"
            description="You have not made any staycation reservations yet. Explore our signature villas and book your private getaway."
            action={
              <Link href="/#villas">
                <Button variant="gold">Explore Signature Villas</Button>
              </Link>
            }
          />
        ) : (
          <div className="space-y-6">
            {bookings.map((booking) => {
              const staycationName =
                booking.staycation?.name ||
                booking.staycation?.house_name ||
                "Dicimulacion Villa";
              const staycationLocation =
                booking.staycation?.location ||
                booking.staycation?.house_location ||
                "Tagaytay, Cavite";
              const staycationImage =
                booking.staycation?.image_url ||
                booking.staycation?.house_image ||
                "/assets/placeholder.jpg";

              const startDateStr = booking.stay?.start_date || booking.start_date || "";
              const endDateStr = booking.stay?.end_date || booking.end_date || "";
              const guestCount = booking.guest?.guest_number || booking.guest_number || 1;
              const totalAmount = parseFloat(booking.pricing?.total_price || String(booking.total_price || 0)) || 0;
              const paidAmount = parseFloat(booking.pricing?.amount_paid || String(booking.amount_paid || 0)) || 0;
              const paymentStatus = booking.payment?.status || booking.payment_status || "pending";
              const paymentMethod = (booking.payment?.method || booking.payment_method || "gcash").toUpperCase();
              const canCancel = booking.can?.cancel ?? (booking.status === "pending" || booking.status === "waiting" || booking.status === "approved");

              return (
                <div
                  key={booking.id}
                  className="rounded-2xl border border-border/80 bg-card p-6 shadow-subtle hover:shadow-card transition-all flex flex-col md:flex-row gap-6 justify-between items-start md:items-center"
                >
                  {/* Left: Thumbnail & Details */}
                  <div className="flex flex-col sm:flex-row gap-5 items-start sm:items-center">
                    <div className="h-28 w-28 rounded-xl overflow-hidden bg-muted shrink-0 relative">
                      <img
                        src={staycationImage}
                        alt={staycationName}
                        className="h-full w-full object-cover"
                      />
                    </div>

                    <div className="space-y-1.5">
                      <div className="flex items-center gap-2.5 flex-wrap">
                        <h3 className="font-serif text-lg font-bold text-foreground">
                          {staycationName}
                        </h3>
                        <StatusBadge status={booking.status} />
                        <StatusBadge status={paymentStatus} />
                      </div>

                      <p className="text-xs text-muted-foreground flex items-center gap-1.5">
                        <MapPin className="h-3.5 w-3.5 text-accent" />
                        {staycationLocation}
                      </p>

                      <div className="flex flex-wrap items-center gap-4 text-xs text-foreground/80 pt-1">
                        <span className="flex items-center gap-1">
                          <Calendar className="h-3.5 w-3.5 text-primary" />
                          {startDateStr ? formatDate(startDateStr) : "TBD"} –{" "}
                          {endDateStr ? formatDate(endDateStr) : "TBD"}
                        </span>
                        <span className="flex items-center gap-1">
                          <Users className="h-3.5 w-3.5 text-primary" />
                          {guestCount} {guestCount === 1 ? "Guest" : "Guests"}
                        </span>
                        <span className="flex items-center gap-1 font-semibold text-primary">
                          <CreditCard className="h-3.5 w-3.5" />
                          {formatPHP(totalAmount)} ({paymentMethod})
                        </span>
                      </div>

                      <p className="text-[11px] text-muted-foreground">
                        Ref #: <span className="font-mono font-semibold text-foreground">{booking.reference}</span>
                      </p>
                    </div>
                  </div>

                  {/* Right: Amounts & Actions */}
                  <div className="flex flex-row md:flex-col items-center md:items-end gap-2.5 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-4 md:pt-0 border-border/60">
                    <div className="text-right">
                      <span className="text-[11px] text-muted-foreground block">Verified Paid:</span>
                      <span className="font-serif font-bold text-sm text-foreground">
                        {formatPHP(paidAmount)}
                      </span>
                    </div>

                    <div className="flex items-center gap-2">
                      {canCancel && (
                        <Button
                          variant="outline"
                          size="sm"
                          disabled={cancelMutation.isPending}
                          className="text-xs text-destructive hover:bg-destructive/10 border-destructive/30"
                          onClick={() => handleCancelClick(booking)}
                        >
                          <XCircle className="h-3.5 w-3.5 mr-1" />
                          {cancelMutation.isPending ? "Cancelling..." : "Cancel Reservation"}
                        </Button>
                      )}

                      {booking.staycation?.id && (
                        <Link href={`/staycation/${booking.staycation.id}`}>
                          <Button variant="ghost" size="sm" className="text-xs gap-1">
                            <ExternalLink className="h-3.5 w-3.5" />
                            View Listing
                          </Button>
                        </Link>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
