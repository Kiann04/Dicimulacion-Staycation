'use client';

import React, { useState } from 'react';
import { StaycationDetails, AvailabilityResult } from '@/lib/types/staycation';
import { staycationService } from '@/lib/api/staycation-service';
import { PriceDisplay } from '@/components/shared/PriceDisplay';
import { Button } from '@/components/ui/Button';
import { formatCurrency } from '@/lib/utils/formatters';

export interface StaycationBookingCardProps {
  staycation: StaycationDetails;
}

export const StaycationBookingCard: React.FC<StaycationBookingCardProps> = ({ staycation }) => {
  // Default dates: tomorrow to 3 days later
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const checkoutDefault = new Date();
  checkoutDefault.setDate(checkoutDefault.getDate() + 3);

  const formatDateForInput = (d: Date) => d.toISOString().split('T')[0];

  const [checkIn, setCheckIn] = useState(formatDateForInput(tomorrow));
  const [checkOut, setCheckOut] = useState(formatDateForInput(checkoutDefault));
  const [guests, setGuests] = useState(2);
  const [isChecking, setIsChecking] = useState(false);
  const [validationError, setValidationError] = useState<string | null>(null);
  const [availabilityResult, setAvailabilityResult] = useState<AvailabilityResult | null>(null);

  const handleCheckAvailability = async (e: React.FormEvent) => {
    e.preventDefault();
    setValidationError(null);

    const start = new Date(checkIn);
    const end = new Date(checkOut);

    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
      setValidationError('Please select a checkout date that is after your check-in date.');
      return;
    }

    setIsChecking(true);
    try {
      const res = await staycationService.checkAvailability(
        staycation.id,
        checkIn,
        checkOut,
        guests
      );
      setAvailabilityResult(res);
    } catch {
      setValidationError('Unable to check availability at this time. Please try again.');
    } finally {
      setIsChecking(false);
    }
  };

  const maxGuests = staycation.maxGuests || 6;

  return (
    <div id="booking-widget" className="sticky top-28 rounded-3xl border border-slate-200/90 bg-white p-6 shadow-lg space-y-6">
      {/* Header Price */}
      <div className="flex items-baseline justify-between border-b border-slate-100 pb-5">
        <div>
          <PriceDisplay
            price={staycation.pricePerNight}
            originalPrice={staycation.originalPricePerNight}
            currency={staycation.currency || 'PHP'}
            size="lg"
          />
        </div>
        <span className="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
          Available
        </span>
      </div>

      {/* Booking Form */}
      <form onSubmit={handleCheckAvailability} className="space-y-4">
        {/* Date Inputs Box */}
        <div className="rounded-2xl border border-slate-200 overflow-hidden bg-slate-50/50">
          <div className="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
            <div className="p-3">
              <label htmlFor="check-in-date" className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                CHECK-IN
              </label>
              <input
                id="check-in-date"
                type="date"
                value={checkIn}
                onChange={(e) => {
                  setCheckIn(e.target.value);
                  setValidationError(null);
                  setAvailabilityResult(null);
                }}
                min={formatDateForInput(new Date())}
                required
                className="w-full bg-transparent text-xs font-semibold text-slate-900 focus:outline-none cursor-pointer mt-1"
              />
            </div>
            <div className="p-3">
              <label htmlFor="check-out-date" className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                CHECKOUT
              </label>
              <input
                id="check-out-date"
                type="date"
                value={checkOut}
                onChange={(e) => {
                  setCheckOut(e.target.value);
                  setValidationError(null);
                  setAvailabilityResult(null);
                }}
                min={checkIn}
                required
                className="w-full bg-transparent text-xs font-semibold text-slate-900 focus:outline-none cursor-pointer mt-1"
              />
            </div>
          </div>

          {/* Guest Count */}
          <div className="p-3">
            <label htmlFor="guests-count" className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
              GUESTS
            </label>
            <select
              id="guests-count"
              value={guests}
              onChange={(e) => {
                setGuests(Number(e.target.value));
                setAvailabilityResult(null);
              }}
              className="w-full bg-transparent text-xs font-semibold text-slate-900 focus:outline-none cursor-pointer mt-1"
            >
              {Array.from({ length: maxGuests }, (_, i) => i + 1).map((num) => (
                <option key={num} value={num}>
                  {num} {num === 1 ? 'guest' : 'guests'} (max {maxGuests})
                </option>
              ))}
            </select>
          </div>
        </div>

        {validationError && (
          <p className="text-xs text-rose-600 font-medium" role="alert">
            {validationError}
          </p>
        )}

        {/* CTA Button */}
        <Button
          type="submit"
          variant="primary"
          size="lg"
          isLoading={isChecking}
          className="w-full shadow-md font-semibold text-sm"
        >
          Check Availability
        </Button>
      </form>

      {/* Availability Result Status Banner */}
      {availabilityResult && (
        <div
          className={`p-4 rounded-2xl text-xs space-y-3 animate-in fade-in duration-200 border ${
            availabilityResult.isAvailable
              ? 'bg-emerald-50/80 border-emerald-200 text-emerald-950'
              : 'bg-rose-50 border-rose-200 text-rose-950'
          }`}
          role="status"
        >
          <div className="flex items-center gap-2 font-semibold">
            <span>{availabilityResult.isAvailable ? '✓' : '✕'}</span>
            <span>{availabilityResult.message}</span>
          </div>

          {availabilityResult.priceBreakdown && (
            <div className="space-y-2 border-t border-emerald-200/80 pt-3 text-slate-700">
              <div className="flex justify-between">
                <span>
                  {formatCurrency(availabilityResult.priceBreakdown.pricePerNight)} × {availabilityResult.priceBreakdown.nights} nights
                </span>
                <span>{formatCurrency(availabilityResult.priceBreakdown.baseTotal)}</span>
              </div>
              {availabilityResult.priceBreakdown.cleaningFee && (
                <div className="flex justify-between">
                  <span>Cleaning fee</span>
                  <span>{formatCurrency(availabilityResult.priceBreakdown.cleaningFee)}</span>
                </div>
              )}
              {availabilityResult.priceBreakdown.serviceFee && (
                <div className="flex justify-between">
                  <span>Service fee (8%)</span>
                  <span>{formatCurrency(availabilityResult.priceBreakdown.serviceFee)}</span>
                </div>
              )}
              {availabilityResult.priceBreakdown.taxes && (
                <div className="flex justify-between">
                  <span>Taxes & occupancy</span>
                  <span>{formatCurrency(availabilityResult.priceBreakdown.taxes)}</span>
                </div>
              )}
              <div className="flex justify-between border-t border-emerald-300/80 pt-2 font-bold text-slate-950 text-sm">
                <span>Total Estimated</span>
                <span>{formatCurrency(availabilityResult.priceBreakdown.total)}</span>
              </div>
            </div>
          )}

          {availabilityResult.isInformationalOnly && (
            <p className="text-[10px] text-slate-500 pt-1 border-t border-emerald-100">
              * Note: Availability check is informational. Dates and rates are confirmed during reservation.
            </p>
          )}
        </div>
      )}

      {/* Disclaimers & Trust notes */}
      <div className="text-center space-y-1.5 pt-2">
        <p className="text-[11px] text-slate-400">
          You won&apos;t be charged yet.
        </p>
        <p className="text-[10px] text-slate-400">
          Free cancellation available up to standard host policy.
        </p>
      </div>
    </div>
  );
};
