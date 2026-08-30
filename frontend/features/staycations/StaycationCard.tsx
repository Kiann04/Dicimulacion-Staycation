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
    staycation.images?.[0]?.url;
  const coverAlt = staycation.coverImage?.alt || staycation.title || 'Staycation photo';
  const locationLabel = staycation.location?.city || '';
  const stayId = String(staycation.id);

  const maxGuests = staycation.capacity?.maximumGuests ?? staycation.maxGuests;
  const isBookable = staycation.isAvailable !== false && staycation.availabilityStatus !== 'unavailable';

  return (
    <article className="group flex flex-col rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition-all duration-300 overflow-hidden">
      {/* Image Container */}
      <Link
        href={`/staycations/${stayId}`}
        className="relative aspect-4/3 w-full overflow-hidden bg-slate-100 block"
        tabIndex={-1}
        aria-hidden="true"
      >
        {coverImageUrl ? (
          <Image
            src={coverImageUrl}
            alt={coverAlt}
            fill
            sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
            className="object-cover group-hover:scale-105 transition-transform duration-500"
          />
        ) : (
          <div className="h-full w-full flex flex-col items-center justify-center bg-slate-100 text-slate-400 gap-1 text-xs">
            <svg className="w-8 h-8 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>No photo available</span>
          </div>
        )}
        
        {/* Top Badges */}
        <div className="absolute top-3 left-3 right-3 flex items-center justify-between pointer-events-none">
          {staycation.badge ? (
            <Badge variant="primary" size="sm" className="shadow-xs backdrop-blur-xs font-semibold">
              {staycation.badge}
            </Badge>
          ) : !isBookable ? (
            <span className="bg-slate-900/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-md">
              OFFLINE
            </span>
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
          {locationLabel ? (
            <p className="text-xs font-medium text-slate-500 uppercase tracking-wider truncate">
              {locationLabel}
            </p>
          ) : <div />}
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

        {/* Description/Tagline */}
        {(staycation.tagline || staycation.description) && (
          <p className="mt-1 text-xs text-slate-500 line-clamp-2 leading-relaxed">
            {staycation.tagline || staycation.description}
          </p>
        )}

        {/* Real Specs (only if present) */}
        <div className="mt-3 flex items-center gap-2 text-xs text-slate-600 border-t border-slate-100 pt-3 flex-wrap">
          {maxGuests && <span>{formatGuestCount(maxGuests)}</span>}
          {staycation.bedrooms !== undefined && (
            <>
              <span className="text-slate-300">•</span>
              <span>{formatBedroomCount(staycation.bedrooms)}</span>
            </>
          )}
          {staycation.bathrooms !== undefined && (
            <>
              <span className="text-slate-300">•</span>
              <span>{formatBathroomCount(staycation.bathrooms)}</span>
            </>
          )}
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
