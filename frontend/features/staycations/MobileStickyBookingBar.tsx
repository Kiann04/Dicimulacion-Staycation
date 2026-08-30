'use client';

import React from 'react';
import { PriceDisplay } from '@/components/shared/PriceDisplay';
import { Button } from '@/components/ui/Button';

export interface MobileStickyBookingBarProps {
  pricePerNight: string | number;
  originalPricePerNight?: string | number;
  currency?: string;
  isBookable?: boolean;
  targetWidgetId?: string;
  targetCheckInId?: string;
  onCheckAvailabilityClick?: () => void;
}

export const MobileStickyBookingBar: React.FC<MobileStickyBookingBarProps> = ({
  pricePerNight,
  originalPricePerNight,
  currency = 'PHP',
  isBookable = true,
  targetWidgetId = 'mobile-booking-widget',
  targetCheckInId = 'mobile-check-in-date',
  onCheckAvailabilityClick,
}) => {
  const scrollToBooking = () => {
    if (onCheckAvailabilityClick) {
      onCheckAvailabilityClick();
      return;
    }
    const widget = document.getElementById(targetWidgetId);
    if (widget) {
      widget.scrollIntoView({ behavior: 'smooth', block: 'center' });
      const checkInInput = document.getElementById(targetCheckInId);
      if (checkInInput) {
        checkInInput.focus();
      }
    }
  };

  return (
    <div className="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-3 shadow-lg">
      <div className="flex items-center justify-between gap-4 max-w-lg mx-auto">
        <div>
          <PriceDisplay
            price={pricePerNight}
            originalPrice={originalPricePerNight}
            currency={currency}
            size="sm"
          />
        </div>
        <Button
          size="md"
          variant="primary"
          onClick={scrollToBooking}
          disabled={!isBookable}
          className="shadow-sm font-semibold disabled:opacity-50"
        >
          {isBookable ? 'Check Availability' : 'Unavailable'}
        </Button>
      </div>
    </div>
  );
};
