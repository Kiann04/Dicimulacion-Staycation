import React from 'react';
import { formatCurrency } from '@/lib/utils/formatters';
import { cn } from '@/lib/utils/cn';

export interface PriceDisplayProps {
  price: number | string;
  originalPrice?: number | string;
  currency?: string;
  periodLabel?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  className?: string;
}

export const PriceDisplay: React.FC<PriceDisplayProps> = ({
  price,
  originalPrice,
  currency = 'PHP',
  periodLabel = 'night',
  size = 'md',
  className,
}) => {
  const sizes = {
    sm: {
      price: 'text-base font-bold',
      period: 'text-xs text-slate-500',
      orig: 'text-xs text-slate-400 line-through',
    },
    md: {
      price: 'text-lg font-bold',
      period: 'text-xs text-slate-500',
      orig: 'text-xs text-slate-400 line-through',
    },
    lg: {
      price: 'text-2xl font-bold',
      period: 'text-sm text-slate-500',
      orig: 'text-sm text-slate-400 line-through',
    },
    xl: {
      price: 'text-3xl font-extrabold',
      period: 'text-base text-slate-500',
      orig: 'text-base text-slate-400 line-through',
    },
  };

  const numPrice = typeof price === 'number' ? price : Number(price) || 0;
  const numOriginal = typeof originalPrice === 'number' ? originalPrice : Number(originalPrice) || 0;
  const hasDiscount = Boolean(originalPrice && numOriginal > numPrice);

  return (
    <div className={cn('inline-flex items-baseline gap-1.5', className)}>
      {hasDiscount && (
        <span className={sizes[size].orig}>
          {formatCurrency(originalPrice, currency)}
        </span>
      )}
      <span className={cn('text-slate-900', sizes[size].price)}>
        {formatCurrency(price, currency)}
      </span>
      {periodLabel && (
        <span className={sizes[size].period}>
          / {periodLabel}
        </span>
      )}
    </div>
  );
};
