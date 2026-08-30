'use client';

import React from 'react';
import { PriceDisplay } from '@/components/shared/PriceDisplay';
import { Button } from '@/components/ui/Button';

export interface MobileStickyBookingBarProps {
  pricePerNight: number;
  originalPricePerNight?: number;
  currency?: string;
  onCheckAvailabilityClick?: () => void;
}

export const MobileStickyBookingBar: React.FC<MobileStickyBookingBarProps> = ({
  pricePerNight,
  originalPricePerNight,
  currency = 'PHP',
  onCheckAvailabilityClick,
}) => {
  const scrollToBooking = () => {
    if (onCheckAvailabilityClick) {
      onCheckAvailabilityClick();
      return;
    }
    const widget = document.getElementById('booking-widget');
    if (widget) {
      widget.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <div className="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-3 shadow-lg">
      <div className="flex items-center justify-between gap-4">
        <div>
          <PriceDisplay
            price={pricePerNight}
            originalPrice={originalPricePerNight}
            currency={currency}
            size="sm"
          />
          <p className="text-[10px] text-slate-500 font-medium">Free cancellation</p>
        </div>
        <Button
          size="md"
          variant="primary"
          onClick={scrollToBooking}
          className="shadow-sm font-semibold"
        >
          Check Availability
        </Button>
      </div>
    </div>
  );
};
