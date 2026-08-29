"use client";

import React, { useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { staycationService } from "@/lib/services/staycationService";
import { inquiryService } from "@/lib/services/inquiryService";
import { formatPHP } from "@/lib/utils";
import { MOCK_REVIEWS } from "@/lib/mockData";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  MapPin,
  Users,
  Star,
  Sparkles,
  Wifi,
  Tv,
  Waves,
  Flame,
  Coffee,
  Heart,
  CheckCircle2,
  Calendar,
  ArrowRight,
  ShieldCheck,
  Send,
} from "lucide-react";

export default function HomePage() {
  const { data: staycations = [], isLoading } = useQuery({
    queryKey: ["staycations", "available"],
    queryFn: () => staycationService.getAll({ availability: "available" }),
  });

  // Contact form state
  const [inquiryEmail, setInquiryEmail] = useState("");
  const [inquiryMsg, setInquiryMsg] = useState("");
  const [isSendingInquiry, setIsSendingInquiry] = useState(false);
  const [inquirySuccess, setInquirySuccess] = useState(false);

  const handleInquirySubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!inquiryEmail || !inquiryMsg) return;
    setIsSendingInquiry(true);

    const formData = new FormData();
    formData.append("email", inquiryEmail);
    formData.append("message", inquiryMsg);

    try {
      await inquiryService.send(formData);
      setInquirySuccess(true);
      setInquiryEmail("");
      setInquiryMsg("");
      setTimeout(() => setInquirySuccess(false), 6000);
    } catch {
      alert("Failed to send inquiry. Please try again.");
    } finally {
      setIsSendingInquiry(false);
    }
  };

  const amenities = [
    {
      icon: <Waves className="h-6 w-6 text-primary" />,
      title: "Private Heated Pools",
      description: "Exclusive swimming pools with temperature control for cozy night dips.",
    },
    {
      icon: <Tv className="h-6 w-6 text-primary" />,
      title: "Smart HD Karaoke",
      description: "Premium sound systems with dual wireless microphones for bonding moments.",
    },
    {
      icon: <Wifi className="h-6 w-6 text-primary" />,
      title: "200Mbps Fiber Wi-Fi",
      description: "Seamless high-speed internet throughout the property for streaming & WFH.",
    },
    {
      icon: <Flame className="h-6 w-6 text-primary" />,
      title: "Bonfire & BBQ Deck",
      description: "Outdoor grill and bonfire lounge setup under the cool Tagaytay breeze.",
    },
    {
      icon: <Coffee className="h-6 w-6 text-primary" />,
      title: "Complete Chef's Kitchen",
      description: "Fully equipped with microwave, refrigerator, rice cooker, and dinnerware.",
    },
    {
      icon: <Heart className="h-6 w-6 text-primary" />,
      title: "Pet-Friendly Sanctuary",
      description: "Fur babies are warmly welcomed with dedicated garden lawns to play in.",
    },
  ];

  return (
    <div className="flex flex-col min-h-screen">
      {/* 1. HERO SECTION */}
      <section className="relative min-h-[90vh] flex items-center justify-center overflow-hidden bg-primary-900 text-white">
        {/* Background Image with Overlay */}
        <div
          className="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-40 scale-105 transition-transform duration-1000"
          style={{
            backgroundImage: `url('https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=1600&auto=format&fit=crop&q=80')`,
          }}
        />
        <div className="absolute inset-0 bg-gradient-to-t from-primary-950 via-primary-900/60 to-transparent" />

        <div className="container relative z-10 mx-auto px-6 py-20 text-center max-w-4xl">
          <div className="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-md px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gold-300 border border-white/15 mb-6">
            <Sparkles className="h-3.5 w-3.5" />
            <span>Luxury Staycation Retreats in Cavite</span>
          </div>

          <h1 className="font-serif text-4xl sm:text-5xl md:text-6xl font-bold tracking-tight leading-[1.15] text-white">
            Unwind in Your Private Sanctuary
          </h1>
          <p className="mt-5 text-base sm:text-lg md:text-xl text-primary-100 max-w-2xl mx-auto font-normal leading-relaxed">
            Heated private pools, scenic mountain breezes, karaoke lounges, and unforgettable bonding moments with loved ones.
          </p>

          {/* Quick Search Card */}
          <div className="mt-10 p-4 sm:p-5 rounded-2xl bg-white/95 text-foreground backdrop-blur-xl shadow-2xl border border-white/20 max-w-3xl mx-auto text-left">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">
                  Destination
                </label>
                <div className="flex items-center gap-2 border border-input rounded-xl px-3 py-2.5 bg-background">
                  <MapPin className="h-4 w-4 text-primary shrink-0" />
                  <span className="text-sm font-medium truncate">Tagaytay & Silang, Cavite</span>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1.5">
                  Guests
                </label>
                <div className="flex items-center gap-2 border border-input rounded-xl px-3 py-2.5 bg-background">
                  <Users className="h-4 w-4 text-primary shrink-0" />
                  <span className="text-sm font-medium">2 to 12 Guests</span>
                </div>
              </div>

              <div className="flex items-end">
                <a href="#villas" className="w-full">
                  <Button variant="gold" size="lg" className="w-full font-semibold gap-2 h-11 shadow-md">
                    Explore Villas
                    <ArrowRight className="h-4 w-4" />
                  </Button>
                </a>
              </div>
            </div>
          </div>

          {/* Trust Highlights */}
          <div className="mt-8 flex flex-wrap items-center justify-center gap-6 text-xs text-primary-200">
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="h-4 w-4 text-gold-300" /> 100% Private Exclusive Use
            </span>
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="h-4 w-4 text-gold-300" /> Instant GCash & BPI Confirmation
            </span>
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="h-4 w-4 text-gold-300" /> 24/7 On-Site Guest Concierge
            </span>
          </div>
        </div>
      </section>

      {/* 2. FEATURED VILLAS SECTION */}
      <section id="villas" className="py-20 bg-background">
        <div className="container mx-auto px-6">
          <div className="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
            <div>
              <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-2">
                Curated Properties
              </span>
              <h2 className="font-serif text-3xl sm:text-4xl font-bold text-foreground">
                Our Signature Staycation Villas
              </h2>
            </div>
            <p className="text-sm text-muted-foreground max-w-md">
              Each property is fully sanitized, privately secluded, and equipped with hotel-grade linens and amenities.
            </p>
          </div>

          {isLoading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {[1, 2, 3].map((i) => (
                <div key={i} className="h-96 rounded-2xl bg-muted/60 animate-pulse" />
              ))}
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
              {staycations.map((villa) => (
                <div
                  key={villa.id}
                  className="group rounded-2xl border border-border/80 bg-card overflow-hidden shadow-subtle hover:shadow-elevated transition-all duration-300 flex flex-col"
                >
                  {/* Image Container */}
                  <div className="relative h-64 w-full overflow-hidden bg-muted">
                    <img
                      src={villa.image_url || villa.house_image}
                      alt={villa.house_name}
                      className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    <div className="absolute top-3.5 left-3.5">
                      <Badge variant="emerald" className="shadow-sm">
                        Available for Booking
                      </Badge>
                    </div>
                    <div className="absolute bottom-3.5 right-3.5 rounded-xl bg-black/70 backdrop-blur-md px-3 py-1.5 text-white text-xs font-semibold">
                      {formatPHP(villa.house_price)} <span className="font-normal text-white/80">/ night</span>
                    </div>
                  </div>

                  {/* Body Content */}
                  <div className="p-6 flex-1 flex flex-col justify-between">
                    <div>
                      <div className="flex items-center gap-1.5 text-xs text-muted-foreground mb-2">
                        <MapPin className="h-3.5 w-3.5 text-accent shrink-0" />
                        <span className="truncate">{villa.house_location}</span>
                      </div>

                      <h3 className="font-serif text-xl font-bold text-foreground group-hover:text-primary transition-colors line-clamp-1">
                        {villa.house_name}
                      </h3>

                      <p className="mt-2.5 text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                        {villa.house_description}
                      </p>

                      <div className="mt-4 flex items-center justify-between text-xs py-3 border-y border-border/60">
                        <div className="flex items-center gap-1.5 font-medium text-foreground">
                          <Users className="h-4 w-4 text-primary" />
                          <span>Up to 12 Guests</span>
                        </div>
                        <div className="flex items-center gap-1 font-semibold text-amber-600">
                          <Star className="h-4 w-4 fill-amber-500 text-amber-500" />
                          <span>{villa.average_rating || "4.9"}</span>
                          <span className="text-muted-foreground font-normal">({villa.total_reviews || 30})</span>
                        </div>
                      </div>
                    </div>

                    <div className="mt-5">
                      <Link href={`/staycation/${villa.id}`}>
                        <Button variant="default" className="w-full font-semibold gap-2 rounded-xl">
                          Check Availability & Book
                          <ArrowRight className="h-4 w-4" />
                        </Button>
                      </Link>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </section>

      {/* 3. AMENITIES SECTION */}
      <section id="amenities" className="py-20 bg-secondary/30 border-y border-border/60">
        <div className="container mx-auto px-6">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-2">
              Everything Included
            </span>
            <h2 className="font-serif text-3xl sm:text-4xl font-bold text-foreground">
              Designed For Relaxation & Fun
            </h2>
            <p className="mt-3 text-sm text-muted-foreground">
              We provide complete amenities so you only need to bring your food, clothes, and vacation mood.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            {amenities.map((item, idx) => (
              <div
                key={idx}
                className="rounded-2xl border border-border/60 bg-card p-6 shadow-subtle hover:border-primary/40 transition-all duration-200"
              >
                <div className="h-12 w-12 rounded-xl bg-primary-50 dark:bg-primary-950/60 flex items-center justify-center mb-4">
                  {item.icon}
                </div>
                <h3 className="font-serif text-lg font-bold text-foreground mb-1.5">{item.title}</h3>
                <p className="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                  {item.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 4. REVIEWS SECTION */}
      <section id="reviews" className="py-20 bg-background">
        <div className="container mx-auto px-6">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <span className="text-xs font-bold uppercase tracking-widest text-accent block mb-2">
              Verified Guest Feedback
            </span>
            <h2 className="font-serif text-3xl sm:text-4xl font-bold text-foreground">
              Loved by Over 500+ Happy Guests
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {MOCK_REVIEWS.map((review) => (
              <div
                key={review.id}
                className="rounded-2xl border border-border/80 bg-card p-6 shadow-subtle flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center gap-1 text-amber-500 mb-3.5">
                    {[...Array(review.rating)].map((_, i) => (
                      <Star key={i} className="h-4 w-4 fill-amber-500" />
                    ))}
                  </div>
                  <p className="text-xs sm:text-sm text-foreground/90 leading-relaxed italic">
                    &ldquo;{review.comment}&rdquo;
                  </p>
                </div>

                <div className="mt-6 pt-4 border-t border-border flex items-center gap-3">
                  <div className="h-9 w-9 rounded-full bg-primary/10 text-primary font-semibold text-xs flex items-center justify-center overflow-hidden">
                    {review.user?.profile_photo_url ? (
                      <img src={review.user.profile_photo_url} alt="" className="h-full w-full object-cover" />
                    ) : (
                      review.user?.name.charAt(0) || "G"
                    )}
                  </div>
                  <div>
                    <h4 className="text-xs font-bold text-foreground">{review.user?.name}</h4>
                    <span className="text-[10px] text-muted-foreground block">
                      Stayed at {review.booking?.staycation?.house_name || "Dicimulacion Villa"}
                    </span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 5. CONTACT & INQUIRY SECTION */}
      <section id="contact" className="py-20 bg-primary-950 text-white">
        <div className="container mx-auto px-6 max-w-5xl">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
              <span className="text-xs font-bold uppercase tracking-widest text-gold-300 block mb-2">
                Have Special Requests?
              </span>
              <h2 className="font-serif text-3xl sm:text-4xl font-bold text-white leading-tight">
                Send Us an Inquiry
              </h2>
              <p className="mt-4 text-sm text-primary-200 leading-relaxed">
                Whether you&apos;re organizing a corporate retreat, family reunion, photoshoot, or intimate wedding staycation, our concierge team is happy to assist.
              </p>

              <div className="mt-8 space-y-4 text-sm text-primary-100">
                <div className="flex items-center gap-3">
                  <div className="h-8 w-8 rounded-full bg-white/10 flex items-center justify-center text-gold-300">
                    <ShieldCheck className="h-4 w-4" />
                  </div>
                  <span>Instant email response within 2 hours</span>
                </div>
                <div className="flex items-center gap-3">
                  <div className="h-8 w-8 rounded-full bg-white/10 flex items-center justify-center text-gold-300">
                    <Calendar className="h-4 w-4" />
                  </div>
                  <span>Custom date reservations & exclusive hold</span>
                </div>
              </div>
            </div>

            {/* Form */}
            <div className="rounded-2xl bg-white/10 backdrop-blur-xl p-6 sm:p-8 border border-white/15 shadow-2xl">
              {inquirySuccess ? (
                <div className="text-center py-8">
                  <CheckCircle2 className="h-12 w-12 text-emerald-400 mx-auto mb-3" />
                  <h4 className="font-serif text-xl font-bold text-white">Message Sent!</h4>
                  <p className="text-xs text-primary-200 mt-1">
                    Thank you! Our concierge has received your inquiry and will email you shortly.
                  </p>
                </div>
              ) : (
                <form onSubmit={handleInquirySubmit} className="space-y-4">
                  <div>
                    <label className="block text-xs font-semibold text-primary-100 uppercase tracking-wider mb-1">
                      Your Email Address
                    </label>
                    <Input
                      type="email"
                      required
                      value={inquiryEmail}
                      onChange={(e) => setInquiryEmail(e.target.value)}
                      placeholder="e.g. name@example.com"
                      className="bg-white/10 border-white/20 text-white placeholder:text-white/40 focus-visible:ring-gold-400"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-primary-100 uppercase tracking-wider mb-1">
                      Message / Special Requirements
                    </label>
                    <Textarea
                      required
                      rows={4}
                      value={inquiryMsg}
                      onChange={(e) => setInquiryMsg(e.target.value)}
                      placeholder="Tell us your desired dates, estimated guests, or special questions..."
                      className="bg-white/10 border-white/20 text-white placeholder:text-white/40 focus-visible:ring-gold-400"
                    />
                  </div>

                  <Button
                    type="submit"
                    variant="gold"
                    isLoading={isSendingInquiry}
                    className="w-full font-semibold h-11 rounded-xl gap-2 mt-2"
                  >
                    <Send className="h-4 w-4" />
                    Submit Inquiry
                  </Button>
                </form>
              )}
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
