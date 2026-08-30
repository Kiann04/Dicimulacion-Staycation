import React from 'react';
import { cn } from '@/lib/utils/cn';

export interface RatingBadgeProps {
  rating?: number | null;
  reviewCount?: number;
  showReviews?: boolean;
  className?: string;
  size?: 'sm' | 'md' | 'lg';
}

export const RatingBadge: React.FC<RatingBadgeProps> = ({
  rating,
  reviewCount,
  showReviews = true,
  className,
  size = 'md',
}) => {
  const sizes = {
    sm: 'text-xs gap-1',
    md: 'text-sm gap-1.5',
    lg: 'text-base gap-2',
  };

  const starSizes = {
    sm: 'h-3.5 w-3.5',
    md: 'h-4 w-4',
    lg: 'h-5 w-5',
  };

  const numericRating = typeof rating === 'number' ? rating : Number(rating);
  const hasValidRating = !isNaN(numericRating) && numericRating > 0;

  if (!hasValidRating) {
    return (
      <div className={cn('inline-flex items-center font-medium text-slate-500', sizes[size], className)}>
        <span className="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-semibold text-slate-600 uppercase">
          New
        </span>
      </div>
    );
  }

  return (
    <div className={cn('inline-flex items-center font-medium text-slate-800', sizes[size], className)}>
      <svg
        className={cn('text-amber-500 fill-amber-500 shrink-0', starSizes[size])}
        viewBox="0 0 20 20"
        aria-hidden="true"
      >
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
      <span className="font-semibold">{numericRating.toFixed(2)}</span>
      {showReviews && typeof reviewCount === 'number' && reviewCount > 0 && (
        <span className="text-slate-500 font-normal">
          ({reviewCount})
        </span>
      )}
    </div>
  );
};
