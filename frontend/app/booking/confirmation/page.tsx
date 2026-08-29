"use client";

import React, { Suspense, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { formatPHP, formatDate } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  CheckCircle2,
  Calendar,
  Users,
  MapPin,
  Clock,
  Printer,
  Home,
  FileText,
  Copy,
  Check,
  ShieldCheck,
} from "lucide-react";

function ConfirmationContent() {
  const searchParams = useSearchParams();
  const [copied, setCopied] = useState(false);

  const refNumber = searchParams?.get("ref") || "DIC-892104";
  const villaName = searchParams?.get("villa") || "Villa Sol y Luna (Private Pool & Balcony)";
  const startDate = searchParams?.get("start") || "2026-09-01";
  const endDate = searchParams?.get("end") || "2026-09-03";
  const guestNumber = searchParams?.get("guests") || "8";
  const paid = Number(searchParams?.get("paid")) || 7000;
  const total = Number(searchParams?.get("total")) || 14000;
  const guestName = searchParams?.get("name") || "Guest";
  const guestEmail = searchParams?.get("email") || "guest@example.com";
  const guestPhone = searchParams?.get("phone") || "0917-123-4567";

  const remainingBalance = Math.max(0, total - paid);

  const handleCopyRef = () => {
    navigator.clipboard.writeText(refNumber);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="max-w-3xl mx-auto space-y-8 animate-in fade-in duration-300">
      {/* Header Banner */}
      <div className="text-center space-y-3">
        <div className="inline-flex h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 items-center justify-center shadow-md animate-bounce-short">
          <CheckCircle2 className="h-9 w-9" />
        </div>
        <span className="text-xs font-bold uppercase tracking-widest text-primary block">
          Reservation Request Submitted
        </span>
        <h1 className="font-serif text-3xl sm:text-4xl font-bold text-foreground">
          Thank you, {guestName}!
        </h1>
        <p className="text-xs sm:text-sm text-muted-foreground max-w-md mx-auto leading-relaxed">
          Your reservation request and payment proof have been received. Our concierge team is verifying your payment and will email your official confirmation voucher shortly.
        </p>
      </div>

      {/* Reference Number Card */}
      <div className="rounded-2xl border border-border bg-card p-6 shadow-subtle flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="text-[10px] uppercase font-bold text-muted-foreground block">
            Booking Reference Number
          </span>
          <span className="font-mono text-xl sm:text-2xl font-bold text-foreground tracking-wider">
            {refNumber}
          </span>
        </div>

        <div className="flex items-center gap-2">
          <Button
            onClick={handleCopyRef}
            variant="outline"
            size="sm"
            className="text-xs gap-1.5"
          >
            {copied ? <Check className="h-3.5 w-3.5 text-emerald-600" /> : <Copy className="h-3.5 w-3.5" />}
            {copied ? "Copied" : "Copy Reference"}
          </Button>

          <Button
            onClick={handlePrint}
            variant="ghost"
            size="sm"
            className="text-xs gap-1.5 print:hidden"
          >
            <Printer className="h-3.5 w-3.5" />
            Print Receipt
          </Button>
        </div>
      </div>

      {/* Booking Summary Box */}
      <div className="rounded-2xl border border-border bg-card overflow-hidden shadow-subtle divide-y divide-border/60">
        {/* Villa Header */}
        <div className="p-6 bg-muted/30">
          <span className="text-xs font-semibold text-primary uppercase tracking-wider block mb-1">
            Reserved Villa
          </span>
          <h2 className="font-serif text-2xl font-bold text-foreground">{villaName}</h2>
          <div className="mt-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
            <span className="flex items-center gap-1.5">
              <Calendar className="h-4 w-4 text-accent" />
              {formatDate(startDate)} — {formatDate(endDate)}
            </span>
            <span className="flex items-center gap-1.5">
              <Users className="h-4 w-4 text-primary" />
              {guestNumber} Registered Guests
            </span>
            <span className="flex items-center gap-1.5">
              <MapPin className="h-4 w-4 text-rose-500" />
              Tagaytay City, Cavite
            </span>
          </div>
        </div>

        {/* Guest Details */}
        <div className="p-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
          <div>
            <span className="text-muted-foreground block text-[10px] uppercase font-bold">
              Guest Name
            </span>
            <span className="font-semibold text-foreground mt-0.5 block">{guestName}</span>
          </div>
          <div>
            <span className="text-muted-foreground block text-[10px] uppercase font-bold">
              Confirmation Email
            </span>
            <span className="font-semibold text-foreground mt-0.5 block">{guestEmail}</span>
          </div>
          <div>
            <span className="text-muted-foreground block text-[10px] uppercase font-bold">
              Contact Number
            </span>
            <span className="font-semibold text-foreground mt-0.5 block">{guestPhone}</span>
          </div>
        </div>

        {/* Payment Breakdown */}
        <div className="p-6 space-y-3 text-xs sm:text-sm">
          <div className="flex justify-between text-muted-foreground">
            <span>Total Villa Rental</span>
            <span className="font-semibold text-foreground">{formatPHP(total)}</span>
          </div>
          <div className="flex justify-between text-emerald-600 dark:text-emerald-400 font-semibold">
            <span>Payment Submitted via GCash/BPI</span>
            <span>-{formatPHP(paid)}</span>
          </div>
          <div className="flex justify-between pt-2 border-t border-border/60 text-base font-bold text-foreground">
            <span>Remaining Balance Due upon Check-In</span>
            <span className="font-serif text-primary">{formatPHP(remainingBalance)}</span>
          </div>
        </div>

        {/* Check-In Reminders */}
        <div className="p-6 bg-primary-50/40 dark:bg-primary-950/20 text-xs space-y-2 text-muted-foreground">
          <div className="flex items-center gap-2 font-bold text-foreground">
            <Clock className="h-4 w-4 text-primary" />
            Check-In & Stay Schedule
          </div>
          <p>
            • <strong>Check-In Time:</strong> 2:00 PM | <strong>Check-Out Time:</strong> 12:00 PM
          </p>
          <p>
            • Please present your government ID and this booking reference upon arrival.
          </p>
          <p>
            • Our on-site concierge team will greet you at the gate and assist with luggage and villa briefing.
          </p>
        </div>
      </div>

      {/* Navigation Actions */}
      <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 print:hidden">
        <Link href="/">
          <Button variant="outline" className="gap-2 text-xs w-full sm:w-auto">
            <Home className="h-4 w-4" />
            Return to Home
          </Button>
        </Link>

        <Link href="/bookings">
          <Button variant="gold" className="gap-2 text-xs font-bold w-full sm:w-auto">
            <FileText className="h-4 w-4" />
            View My Bookings
          </Button>
        </Link>
      </div>
    </div>
  );
}

export default function BookingConfirmationPage() {
  return (
    <div className="min-h-[85vh] py-12 px-6 bg-background">
      <Suspense fallback={<div className="h-96 max-w-3xl mx-auto rounded-2xl bg-muted/60 animate-pulse" />}>
        <ConfirmationContent />
      </Suspense>
    </div>
  );
}
