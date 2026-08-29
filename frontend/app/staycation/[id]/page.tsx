"use client";

import React, { useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { staycationService } from "@/lib/services/staycationService";
import { formatPHP } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { BookingModal } from "@/features/bookings/components/BookingModal";
import { DateGuestSelector } from "@/features/bookings/components/DateGuestSelector";
import { PricingBreakdown } from "@/features/bookings/components/PricingBreakdown";
import {
  MapPin,
  Users,
  Star,
  Sparkles,
  Wifi,
  Tv,
  Waves,
  Flame,
  CheckCircle2,
  Calendar as CalendarIcon,
  ChevronRight,
  ShieldCheck,
  ArrowRight,
} from "lucide-react";

export default function StaycationDetailsPage() {
  const params = useParams();
  const router = useRouter();
  const id = params?.id as string;

  const { data: staycation, isLoading, error } = useQuery({
    queryKey: ["staycation", id],
    queryFn: () => staycationService.getById(id),
    enabled: !!id,
  });

  // Booking Flow State
  const [isBookingModalOpen, setIsBookingModalOpen] = useState(false);
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [guestNumber, setGuestNumber] = useState(2);
  const [paymentOption, setPaymentOption] = useState<"half" | "full">("half");

  if (isLoading) {
    return (
      <div className="container mx-auto py-16 px-6 max-w-6xl">
        <div className="h-8 w-64 bg-muted/60 rounded-lg animate-pulse mb-6" />
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
          <div className="lg:col-span-2 h-[450px] bg-muted/60 rounded-2xl animate-pulse" />
          <div className="h-[450px] bg-muted/60 rounded-2xl animate-pulse" />
        </div>
      </div>
    );
  }

  if (error || !staycation) {
    return (
      <div className="container mx-auto py-20 px-6 text-center">
        <h2 className="text-2xl font-serif font-bold text-foreground">Staycation Not Found</h2>
        <p className="text-sm text-muted-foreground mt-2">The requested property listing does not exist.</p>
        <Button onClick={() => router.push("/")} className="mt-6" variant="gold">
          Back to Listings
        </Button>
      </div>
    );
  }

  return (
    <div className="min-h-screen py-10 bg-background pb-28 sm:pb-12">
      <div className="container mx-auto px-6 max-w-6xl">
        {/* Header Breadcrumb & Title */}
        <div className="mb-8">
          <div className="flex items-center gap-2 text-xs text-muted-foreground mb-3">
            <span onClick={() => router.push("/")} className="hover:text-primary cursor-pointer">
              Home
            </span>
            <ChevronRight className="h-3 w-3" />
            <span className="text-foreground font-medium">{staycation.house_name}</span>
          </div>

          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h1 className="font-serif text-3xl sm:text-4xl font-bold text-foreground">
                {staycation.house_name}
              </h1>
              <div className="flex items-center gap-2 text-sm text-muted-foreground mt-2">
                <MapPin className="h-4 w-4 text-accent" />
                <span>{staycation.house_location}</span>
                <span className="text-border">•</span>
                <span className="text-amber-600 font-semibold flex items-center gap-1">
                  <Star className="h-4 w-4 fill-amber-500 text-amber-500" />
                  {staycation.average_rating || "4.9"} ({staycation.total_reviews || 30} reviews)
                </span>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <Badge variant="emerald" className="text-xs px-3 py-1">
                Verified Private Villa
              </Badge>
            </div>
          </div>
        </div>

        {/* Image Showcase */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 rounded-2xl overflow-hidden shadow-subtle mb-12">
          <div className="md:col-span-2 h-[360px] sm:h-[460px] bg-muted relative">
            <img
              src={staycation.image_url || staycation.house_image}
              alt={staycation.house_name}
              className="h-full w-full object-cover"
            />
          </div>
          <div className="hidden md:flex flex-col gap-4 h-[460px]">
            {staycation.images && staycation.images.length > 0 ? (
              staycation.images.slice(0, 2).map((img, idx) => (
                <div key={idx} className="h-1/2 rounded-xl overflow-hidden bg-muted relative">
                  <img src={img.image_url} alt="" className="h-full w-full object-cover" />
                </div>
              ))
            ) : (
              <>
                <div className="h-1/2 rounded-xl overflow-hidden bg-muted relative">
                  <img
                    src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=600&auto=format&fit=crop&q=80"
                    alt=""
                    className="h-full w-full object-cover"
                  />
                </div>
                <div className="h-1/2 rounded-xl overflow-hidden bg-muted relative">
                  <img
                    src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=600&auto=format&fit=crop&q=80"
                    alt=""
                    className="h-full w-full object-cover"
                  />
                </div>
              </>
            )}
          </div>
        </div>

        {/* Content Layout */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
          {/* Left Column: Details & Amenities */}
          <div className="lg:col-span-2 space-y-10">
            {/* Description */}
            <div>
              <h3 className="font-serif text-2xl font-bold text-foreground mb-4">About this Villa</h3>
              <p className="text-sm sm:text-base text-muted-foreground leading-relaxed">
                {staycation.house_description}
              </p>
            </div>

            {/* Exclusive Amenities */}
            <div className="p-6 rounded-2xl bg-secondary/30 border border-border/60">
              <h4 className="font-serif text-lg font-bold text-foreground mb-4">Exclusive Amenities</h4>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs sm:text-sm text-foreground">
                <div className="flex items-center gap-2.5">
                  <Waves className="h-4 w-4 text-primary" /> Private Heated Pool
                </div>
                <div className="flex items-center gap-2.5">
                  <Tv className="h-4 w-4 text-primary" /> Smart Karaoke Lounge
                </div>
                <div className="flex items-center gap-2.5">
                  <Wifi className="h-4 w-4 text-primary" /> 200Mbps Wi-Fi
                </div>
                <div className="flex items-center gap-2.5">
                  <Flame className="h-4 w-4 text-primary" /> Bonfire & BBQ Grill
                </div>
                <div className="flex items-center gap-2.5">
                  <CheckCircle2 className="h-4 w-4 text-primary" /> Air-conditioned Suites
                </div>
                <div className="flex items-center gap-2.5">
                  <CheckCircle2 className="h-4 w-4 text-primary" /> Complete Kitchen Cookware
                </div>
              </div>
            </div>

            {/* House Policies */}
            <div>
              <h3 className="font-serif text-xl font-bold text-foreground mb-3">House Policies</h3>
              <ul className="space-y-2 text-xs sm:text-sm text-muted-foreground list-disc list-inside">
                <li>Check-in: 2:00 PM | Check-out: 12:00 PM (Noon).</li>
                <li>Base rate covers up to 6 guests. Additional guests are ₱500/head per night.</li>
                <li>Outdoor karaoke permitted until 10:00 PM in consideration of neighborhood tranquility.</li>
                <li>50% downpayment required to secure dates. Remaining balance settleable upon arrival.</li>
              </ul>
            </div>
          </div>

          {/* Right Column: Desktop Sticky Booking Widget */}
          <div className="hidden lg:block">
            <div className="sticky top-24 rounded-2xl border border-border/80 bg-card p-6 shadow-card space-y-5">
              <div className="flex items-baseline justify-between pb-4 border-b border-border/60">
                <div>
                  <span className="font-serif text-3xl font-bold text-foreground">
                    {formatPHP(staycation.house_price)}
                  </span>
                  <span className="text-xs text-muted-foreground ml-1">/ night</span>
                </div>
                <Badge variant="emerald">Instant Hold</Badge>
              </div>

              <DateGuestSelector
                startDate={startDate}
                endDate={endDate}
                guestNumber={guestNumber}
                onStartDateChange={setStartDate}
                onEndDateChange={setEndDate}
                onGuestNumberChange={setGuestNumber}
              />

              <Button
                type="button"
                onClick={() => setIsBookingModalOpen(true)}
                variant="gold"
                size="lg"
                className="w-full font-bold shadow-md gap-2"
              >
                Reserve Dates & Check Availability
                <ArrowRight className="h-4 w-4" />
              </Button>

              <div className="flex items-center justify-center gap-1.5 text-xs text-muted-foreground pt-1">
                <ShieldCheck className="h-4 w-4 text-primary" />
                <span>Zero hidden fees • Verified Host</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Mobile Sticky Booking Footer Bar */}
      <div className="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-card/95 backdrop-blur-md border-t border-border p-4 shadow-elevated">
        <div className="container mx-auto flex items-center justify-between gap-4">
          <div>
            <span className="font-serif text-xl font-bold text-foreground">
              {formatPHP(staycation.house_price)}
            </span>
            <span className="text-[11px] text-muted-foreground block">/ night</span>
          </div>

          <Button
            type="button"
            onClick={() => setIsBookingModalOpen(true)}
            variant="gold"
            size="sm"
            className="font-bold shadow-md gap-1.5 text-xs"
          >
            Check Dates & Book
            <ArrowRight className="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>

      {/* Multi-Step Booking Modal */}
      <BookingModal
        staycation={staycation}
        isOpen={isBookingModalOpen}
        onClose={() => setIsBookingModalOpen(false)}
        initialStartDate={startDate}
        initialEndDate={endDate}
        initialGuests={guestNumber}
      />
    </div>
  );
}
