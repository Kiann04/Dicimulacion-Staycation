import React from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { StaycationSummary } from '@/lib/types/staycation';
import { Badge } from '@/components/ui/Badge';
import { RatingBadge } from '@/components/shared/RatingBadge';
import { PriceDisplay } from '@/components/shared/PriceDisplay';
import { formatBedroomCount, formatBathroomCount, formatGuestCount } from '@/lib/utils/formatters';

export interface StaycationCardProps {
  staycation: StaycationSummary;
}

export const StaycationCard: React.FC<StaycationCardProps> = ({ staycation }) => {
  const coverImageUrl =
    staycation.coverImage?.url ||
    staycation.images?.[0]?.url ||
    'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?auto=format&fit=crop&w=1200&q=80';
  const coverAlt = staycation.coverImage?.alt || staycation.title || 'Staycation property photo';
  const locationLabel = staycation.location
    ? [staycation.location.city, staycation.location.province].filter(Boolean).join(', ')
    : 'Philippines';
  const stayId = String(staycation.id);

  return (
    <article className="group flex flex-col rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-300 overflow-hidden">
      {/* Image Container */}
      <Link
        href={`/staycations/${stayId}`}
        className="relative aspect-4/3 w-full overflow-hidden bg-slate-100 block"
        tabIndex={-1}
        aria-hidden="true"
      >
        <Image
          src={coverImageUrl}
          alt={coverAlt}
          fill
          sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
          className="object-cover group-hover:scale-105 transition-transform duration-500"
        />
        
        {/* Top Badges */}
        <div className="absolute top-3 left-3 right-3 flex items-center justify-between pointer-events-none">
          {staycation.badge ? (
            <Badge variant="primary" size="sm" className="shadow-xs backdrop-blur-xs font-semibold">
              {staycation.badge}
            </Badge>
          ) : <span />}
          
          {staycation.propertyType && (
            <span className="bg-white/90 backdrop-blur-md px-2 py-0.5 rounded-md text-[11px] font-semibold text-slate-800 shadow-xs uppercase tracking-wider">
              {staycation.propertyType}
            </span>
          )}
        </div>
      </Link>

      {/* Content Container */}
      <div className="flex flex-1 flex-col p-5">
        {/* Location & Rating */}
        <div className="flex items-center justify-between gap-2 mb-1.5">
          <p className="text-xs font-medium text-slate-500 uppercase tracking-wider truncate">
            {locationLabel}
          </p>
          <RatingBadge
            rating={staycation.reviews?.rating}
            reviewCount={staycation.reviews?.reviewCount}
            size="sm"
          />
        </div>

        {/* Title */}
        <h3 className="text-base font-bold text-slate-900 group-hover:text-slate-700 transition-colors line-clamp-1">
          <Link href={`/staycations/${stayId}`} className="focus:outline-none focus-visible:underline">
            {staycation.title}
          </Link>
        </h3>

        {/* Tagline */}
        {staycation.tagline && (
          <p className="mt-1 text-xs text-slate-500 line-clamp-2 leading-relaxed">
            {staycation.tagline}
          </p>
        )}

        {/* Property Specs Pill Row */}
        <div className="mt-3 flex items-center gap-2 text-xs text-slate-600 border-t border-slate-100 pt-3">
          <span>{formatGuestCount(staycation.maxGuests)}</span>
          <span className="text-slate-300">•</span>
          <span>{formatBedroomCount(staycation.bedrooms)}</span>
          <span className="text-slate-300">•</span>
          <span>{formatBathroomCount(staycation.bathrooms)}</span>
        </div>

        {/* Footer / Price & CTA */}
        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
          <PriceDisplay
            price={staycation.pricePerNight}
            originalPrice={staycation.originalPricePerNight}
            currency={staycation.currency || 'PHP'}
            size="sm"
          />
          <Link
            href={`/staycations/${stayId}`}
            className="inline-flex items-center text-xs font-semibold text-slate-900 hover:text-slate-600 underline underline-offset-4 focus:outline-none focus-visible:ring-1 focus-visible:ring-slate-900 rounded"
          >
            Details →
          </Link>
        </div>
      </div>
    </article>
  );
};
