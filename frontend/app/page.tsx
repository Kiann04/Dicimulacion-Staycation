import React from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { staycationService } from '@/lib/api/staycation-service';
import { Container } from '@/components/ui/Container';
import { Button } from '@/components/ui/Button';
import { SectionHeading } from '@/components/shared/SectionHeading';
import { StaycationGrid } from '@/features/staycations/StaycationGrid';
import { ErrorState } from '@/components/shared/ErrorState';
import { StaycationSummary } from '@/lib/types/staycation';

export default async function HomePage() {
  let featuredStaycations: StaycationSummary[] = [];
  let loadError: string | null = null;

  try {
    featuredStaycations = await staycationService.getFeaturedStaycations();
  } catch (err) {
    loadError =
      err instanceof Error
        ? err.message
        : 'Unable to connect to the staycation service. Please verify the API is reachable.';
  }

  return (
    <div className="space-y-16 sm:space-y-24">
      {/* Hero Section */}
      <section className="relative overflow-hidden bg-slate-900 text-white py-20 sm:py-28">
        {/* Background Image with Overlay */}
        <div className="absolute inset-0 z-0">
          <Image
            src="https://images.unsplash.com/photo-1580587771525-78b9dba3b914?auto=format&fit=crop&w=2000&q=80"
            alt="Scenic staycation background"
            fill
            priority
            sizes="100vw"
            className="object-cover opacity-25 filter brightness-90"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent" />
        </div>

        <Container size="lg" className="relative z-10">
          <div className="max-w-3xl space-y-6">
            <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-emerald-300 backdrop-blur-xs border border-white/15">
              <span className="h-2 w-2 rounded-full bg-emerald-400 animate-pulse" />
              Staycations
            </span>

            <h1 className="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
              Find Your Ideal Staycation Sanctuary
            </h1>

            <p className="text-base sm:text-xl text-slate-300 leading-relaxed max-w-2xl font-light">
              Explore private villas, scenic ridge chalets, and urban skyline lofts for peaceful weekend getaways and remote work.
            </p>

            {/* Quick Destination Search Bar Shell */}
            <div className="pt-4">
              <div className="bg-white/95 backdrop-blur-md rounded-2xl p-2 sm:p-3 shadow-xl border border-white/20 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 max-w-2xl text-slate-900">
                <div className="flex-1 px-4 py-2">
                  <label htmlFor="hero-destination-input" className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    WHERE TO?
                  </label>
                  <input
                    id="hero-destination-input"
                    type="text"
                    placeholder="Tagaytay, Makati, Batangas, Baguio..."
                    readOnly
                    className="w-full bg-transparent text-sm font-semibold text-slate-800 focus:outline-none placeholder:text-slate-400 cursor-pointer"
                  />
                </div>

                <div className="hidden sm:block h-8 w-px bg-slate-200" />

                <div className="flex-1 px-4 py-2">
                  <label htmlFor="hero-dates-input" className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    DATES & GUESTS
                  </label>
                  <input
                    id="hero-dates-input"
                    type="text"
                    placeholder="Flexible dates • Any guests"
                    readOnly
                    className="w-full bg-transparent text-sm font-semibold text-slate-800 focus:outline-none placeholder:text-slate-400 cursor-pointer"
                  />
                </div>

                <Link href="/staycations" className="shrink-0">
                  <Button size="lg" variant="primary" className="w-full sm:w-auto h-full py-3.5 px-6">
                    Search Stays
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </Container>
      </section>

      {/* Explore Staycations Section */}
      <section>
        <Container size="lg">
          <div className="flex flex-col sm:flex-row sm:items-end justify-between mb-8 gap-4">
            <SectionHeading
              badge="Catalogue"
              title="Explore Stays"
              subtitle="Browse available properties from our public catalogue."
              className="mb-0"
            />
            <Link href="/staycations" className="shrink-0">
              <Button variant="outline" size="sm">
                View All Staycations →
              </Button>
            </Link>
          </div>

          {loadError ? (
            <ErrorState
              title="Could not load staycations"
              message={loadError}
            />
          ) : (
            <StaycationGrid
              staycations={featuredStaycations}
              emptyTitle="No staycations currently listed"
              emptyDescription="Explore our full catalogue of staycation options."
            />
          )}
        </Container>
      </section>

      {/* Trust & Transparency */}
      <section className="bg-white border-y border-slate-200/80 py-16">
        <Container size="lg">
          <SectionHeading
            badge="Features"
            title="Transparent & Reliable Stays"
            subtitle="Clear rates and verified inventory for worry-free bookings."
            centered
          />

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mt-12">
            <div className="p-6 rounded-2xl bg-slate-50/70 border border-slate-200/60 space-y-3">
              <div className="h-12 w-12 rounded-xl bg-white border border-slate-200 text-slate-900 flex items-center justify-center font-bold text-lg shadow-2xs">
                ✓
              </div>
              <h3 className="text-base font-bold text-slate-900">Direct Listings</h3>
              <p className="text-xs text-slate-600 leading-relaxed">
                Browse detailed property information directly from the host system.
              </p>
            </div>

            <div className="p-6 rounded-2xl bg-slate-50/70 border border-slate-200/60 space-y-3">
              <div className="h-12 w-12 rounded-xl bg-white border border-slate-200 text-slate-900 flex items-center justify-center font-bold text-lg shadow-2xs">
                ₱
              </div>
              <h3 className="text-base font-bold text-slate-900">Authoritative Rates</h3>
              <p className="text-xs text-slate-600 leading-relaxed">
                Clear nightly pricing provided directly by the backend reservation service.
              </p>
            </div>

            <div className="p-6 rounded-2xl bg-slate-50/70 border border-slate-200/60 space-y-3">
              <div className="h-12 w-12 rounded-xl bg-white border border-slate-200 text-slate-900 flex items-center justify-center font-bold text-lg shadow-2xs">
                ⚡
              </div>
              <h3 className="text-base font-bold text-slate-900">Instant Availability</h3>
              <p className="text-xs text-slate-600 leading-relaxed">
                Check real-time calendar availability before making reservation inquiries.
              </p>
            </div>

            <div className="p-6 rounded-2xl bg-slate-50/70 border border-slate-200/60 space-y-3">
              <div className="h-12 w-12 rounded-xl bg-white border border-slate-200 text-slate-900 flex items-center justify-center font-bold text-lg shadow-2xs">
                ★
              </div>
              <h3 className="text-base font-bold text-slate-900">Verified Reviews</h3>
              <p className="text-xs text-slate-600 leading-relaxed">
                Ratings aggregated from completed guest stays.
              </p>
            </div>
          </div>
        </Container>
      </section>

      {/* Booking-Oriented CTA Banner */}
      <section>
        <Container size="lg">
          <div className="relative rounded-3xl bg-slate-900 text-white p-8 sm:p-14 overflow-hidden shadow-xl">
            <div className="relative z-10 max-w-2xl space-y-4">
              <span className="text-xs font-semibold uppercase tracking-widest text-emerald-400">
                Plan Your Getaway
              </span>
              <h2 className="text-2xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                Browse our full catalogue of staycations.
              </h2>
              <p className="text-sm sm:text-base text-slate-300 leading-relaxed">
                Discover private villas, lofts, and beach houses across Tagaytay, Makati, Batangas, Baguio, and Siargao.
              </p>
              <div className="pt-4 flex flex-wrap gap-3">
                <Link href="/staycations">
                  <Button size="lg" variant="secondary" className="font-semibold">
                    Explore All Stays
                  </Button>
                </Link>
              </div>
            </div>

            {/* Background Accent Graphics */}
            <div className="absolute -right-20 -bottom-20 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl" />
            <div className="absolute right-20 -top-20 h-80 w-80 rounded-full bg-slate-700/30 blur-2xl" />
          </div>
        </Container>
      </section>
    </div>
  );
}
