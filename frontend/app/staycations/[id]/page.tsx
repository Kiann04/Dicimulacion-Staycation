import React from 'react';
import type { Metadata } from 'next';
import Link from 'next/link';
import Image from 'next/image';
import { notFound } from 'next/navigation';
import { staycationService } from '@/lib/api/staycation-service';
import { Container } from '@/components/ui/Container';
import { Badge } from '@/components/ui/Badge';
import { RatingBadge } from '@/components/shared/RatingBadge';
import { StaycationGallery } from '@/features/staycations/StaycationGallery';
import { StaycationAmenitiesList } from '@/features/staycations/StaycationAmenitiesList';
import { StaycationBookingCard } from '@/features/staycations/StaycationBookingCard';
import { MobileStickyBookingBar } from '@/features/staycations/MobileStickyBookingBar';
import {
  formatBedroomCount,
  formatBathroomCount,
  formatGuestCount,
} from '@/lib/utils/formatters';

interface PageProps {
  params: Promise<{ id: string }>;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { id } = await params;
  const staycation = await staycationService.getStaycationById(id);

  if (!staycation) {
    return {
      title: 'Staycation Not Found',
    };
  }

  const cityLabel = staycation.location?.city ? ` in ${staycation.location.city}` : '';

  return {
    title: `${staycation.title}${cityLabel}`,
    description: staycation.tagline || staycation.description?.slice(0, 160) || 'Verified Staycation in the Philippines',
  };
}

export default async function StaycationDetailPage({ params }: PageProps) {
  const { id } = await params;
  const staycation = await staycationService.getStaycationById(id);

  if (!staycation) {
    notFound();
  }

  const locationParts = [
    staycation.location?.city,
    staycation.location?.province,
    staycation.location?.country,
  ].filter(Boolean);

  const images = staycation.images && staycation.images.length > 0
    ? staycation.images
    : staycation.coverImage
    ? [staycation.coverImage]
    : [];

  return (
    <>
      <Container size="lg" className="py-6 sm:py-10 pb-28 lg:pb-12 space-y-8">
        {/* Breadcrumb Navigation */}
        <nav aria-label="Breadcrumb" className="flex items-center gap-2 text-xs text-slate-500">
          <Link href="/" className="hover:text-slate-900 transition-colors focus-visible:underline">
            Home
          </Link>
          <span aria-hidden="true">/</span>
          <Link href="/staycations" className="hover:text-slate-900 transition-colors focus-visible:underline">
            Staycations
          </Link>
          <span aria-hidden="true">/</span>
          <span className="text-slate-900 font-medium truncate max-w-xs sm:max-w-md">
            {staycation.title}
          </span>
        </nav>

        {/* Title Header */}
        <div className="space-y-2">
          <div className="flex flex-wrap items-center gap-2">
            {staycation.badge && (
              <Badge variant="primary" size="sm">
                {staycation.badge}
              </Badge>
            )}
            {staycation.propertyType && (
              <Badge variant="neutral" size="sm">
                {staycation.propertyType.toUpperCase()}
              </Badge>
            )}
          </div>

          <h1 className="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
            {staycation.title}
          </h1>

          <div className="flex flex-wrap items-center justify-between gap-4 pt-1 text-sm">
            <div className="flex items-center gap-3 text-slate-600 flex-wrap">
              <RatingBadge
                rating={staycation.reviews?.rating}
                reviewCount={staycation.reviews?.reviewCount}
                size="md"
              />
              {locationParts.length > 0 && (
                <>
                  <span aria-hidden="true">•</span>
                  <span className="font-medium text-slate-700">
                    {locationParts.join(', ')}
                  </span>
                </>
              )}
            </div>
          </div>
        </div>

        {/* Image Gallery */}
        <StaycationGallery images={images} title={staycation.title} />

        {/* Main Content Layout: 2 Cols on Desktop */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 sm:gap-12 pt-4">
          {/* Left Column: Details & Amenities */}
          <div className="lg:col-span-7 space-y-10">
            {/* Hosted By & Key Specs Summary */}
            <div className="flex items-center justify-between border-b border-slate-200 pb-6">
              <div className="space-y-1">
                <h2 className="text-lg font-bold text-slate-900">
                  {staycation.host?.name
                    ? `Entire ${staycation.propertyType || 'stay'} hosted by ${staycation.host.name}`
                    : `Entire ${staycation.propertyType || 'stay'}`}
                </h2>
                <div className="flex items-center gap-2 text-xs text-slate-600 flex-wrap">
                  <span>{formatGuestCount(staycation.maxGuests)}</span>
                  <span aria-hidden="true">•</span>
                  <span>{formatBedroomCount(staycation.bedrooms)}</span>
                  <span aria-hidden="true">•</span>
                  <span>{staycation.beds || 1} {(staycation.beds || 1) === 1 ? 'bed' : 'beds'}</span>
                  <span aria-hidden="true">•</span>
                  <span>{formatBathroomCount(staycation.bathrooms)}</span>
                </div>
              </div>

              {staycation.host?.avatarUrl && (
                <div className="relative h-14 w-14 rounded-full overflow-hidden border-2 border-slate-200 shadow-xs shrink-0">
                  <Image
                    src={staycation.host.avatarUrl}
                    alt={staycation.host.name || 'Host avatar'}
                    fill
                    sizes="56px"
                    className="object-cover"
                  />
                </div>
              )}
            </div>

            {/* Host Highlights Banner */}
            {(staycation.host?.isSuperhost || staycation.checkInTime) && (
              <div className="rounded-2xl bg-slate-50/80 border border-slate-200/80 p-5 space-y-3">
                {staycation.host?.isSuperhost && (
                  <div className="flex items-start gap-3">
                    <div className="h-8 w-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm shrink-0" aria-hidden="true">
                      ★
                    </div>
                    <div>
                      <h3 className="text-sm font-semibold text-slate-900">Experienced Superhost</h3>
                      <p className="text-xs text-slate-500 leading-relaxed">
                        {staycation.host.name} has consistent top ratings
                        {staycation.host.responseRate ? ` and a ${staycation.host.responseRate}` : ''}.
                      </p>
                    </div>
                  </div>
                )}

                {staycation.checkInTime && (
                  <div className="flex items-start gap-3">
                    <div className="h-8 w-8 rounded-lg bg-slate-200 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0" aria-hidden="true">
                      🕒
                    </div>
                    <div>
                      <h3 className="text-sm font-semibold text-slate-900">Check-in & Check-out</h3>
                      <p className="text-xs text-slate-500 leading-relaxed">
                        Check-in at {staycation.checkInTime}
                        {staycation.checkOutTime ? ` • Check-out at ${staycation.checkOutTime}` : ''}
                      </p>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Description */}
            <div className="space-y-4 border-b border-slate-200 pb-8">
              <h3 className="text-lg font-bold text-slate-900">About this stay</h3>
              <p className="text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                {staycation.description || staycation.tagline || 'No detailed description available.'}
              </p>
              {staycation.spaceDescription && (
                <div className="pt-2">
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                    The Space
                  </h4>
                  <p className="text-sm text-slate-700 leading-relaxed">
                    {staycation.spaceDescription}
                  </p>
                </div>
              )}
              {staycation.guestAccessDescription && (
                <div className="pt-2">
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                    Guest Access
                  </h4>
                  <p className="text-sm text-slate-700 leading-relaxed">
                    {staycation.guestAccessDescription}
                  </p>
                </div>
              )}
            </div>

            {/* Amenities Grid */}
            <div className="border-b border-slate-200 pb-8">
              <StaycationAmenitiesList amenities={staycation.amenities} />
            </div>

            {/* House Rules & Policies */}
            <div className="space-y-6 border-b border-slate-200 pb-8">
              <h3 className="text-lg font-bold text-slate-900">Things to know</h3>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                {staycation.houseRules && staycation.houseRules.length > 0 && (
                  <div className="space-y-2">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-900">
                      House Rules
                    </h4>
                    <ul className="space-y-1.5 text-xs text-slate-600">
                      {staycation.houseRules.map((rule, idx) => (
                        <li key={idx} className="flex items-start gap-2">
                          <span className="text-slate-400" aria-hidden="true">•</span>
                          <span>{rule}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}

                {staycation.safetyInfo && staycation.safetyInfo.length > 0 && (
                  <div className="space-y-2">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-slate-900">
                      Safety & Property
                    </h4>
                    <ul className="space-y-1.5 text-xs text-slate-600">
                      {staycation.safetyInfo.map((info, idx) => (
                        <li key={idx} className="flex items-start gap-2">
                          <span className="text-slate-400" aria-hidden="true">•</span>
                          <span>{info}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>

              {staycation.cancellationPolicy && (
                <div className="pt-2">
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-900 mb-1">
                    Cancellation Policy
                  </h4>
                  <p className="text-xs text-slate-600 leading-relaxed">
                    {staycation.cancellationPolicy}
                  </p>
                </div>
              )}
            </div>
          </div>

          {/* Right Column: Desktop Sticky Booking Widget */}
          <div className="hidden lg:block lg:col-span-5">
            <StaycationBookingCard staycation={staycation} />
          </div>
        </div>
      </Container>

      {/* Mobile-only Sticky Booking Bottom Bar */}
      <MobileStickyBookingBar
        pricePerNight={Number(staycation.pricePerNight) || 0}
        originalPricePerNight={staycation.originalPricePerNight ? Number(staycation.originalPricePerNight) : undefined}
        currency={staycation.currency || 'PHP'}
      />
    </>
  );
}
