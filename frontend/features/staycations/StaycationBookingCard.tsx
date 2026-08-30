'use client';

import React, { useState, useId } from 'react';
import { StaycationDetails, AvailabilityResult } from '@/lib/types/staycation';
import { staycationService } from '@/lib/api/staycation-service';
import { PriceDisplay } from '@/components/shared/PriceDisplay';
import { Button } from '@/components/ui/Button';
import { getBookingFormIds } from '@/lib/utils/form-ids';

export { getBookingFormIds };

export interface StaycationBookingCardProps {
  staycation: StaycationDetails;
  idPrefix?: string;
}

export const StaycationBookingCard: React.FC<StaycationBookingCardProps> = ({
  staycation,
  idPrefix,
}) => {
  const generatedId = useId().replace(/:/g, '');
  const prefix = idPrefix !== undefined ? idPrefix : `booking-${generatedId}-`;
  const formIds = getBookingFormIds(prefix);

  const isBookable = staycation.isAvailable !== false && staycation.availabilityStatus !== 'unavailable';

  // Default dates: tomorrow to 3 days later
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const checkoutDefault = new Date();
  checkoutDefault.setDate(checkoutDefault.getDate() + 3);

  const formatDateForInput = (d: Date) => d.toISOString().split('T')[0];

  const [checkIn, setCheckIn] = useState(formatDateForInput(tomorrow));
  const [checkOut, setCheckOut] = useState(formatDateForInput(checkoutDefault));
  const [guests, setGuests] = useState(1);
  const [isChecking, setIsChecking] = useState(false);
  const [validationError, setValidationError] = useState<string | null>(null);
  const [availabilityResult, setAvailabilityResult] = useState<AvailabilityResult | null>(null);

  const handleCheckAvailability = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!isBookable) return;

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
      setValidationError('Unable to connect to the staycation service. Please verify the API is reachable.');
    } finally {
      setIsChecking(false);
    }
  };

  const maxGuests = staycation.capacity?.maximumGuests ?? staycation.maxGuests ?? 1;

  return (
    <div id={formIds.widgetId} className="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-lg space-y-6">
      {/* Header Price & Status */}
      <div className="flex items-baseline justify-between border-b border-slate-100 pb-5">
        <div>
          <PriceDisplay
            price={staycation.pricePerNight}
            originalPrice={staycation.originalPricePerNight}
            currency={staycation.currency || 'PHP'}
            size="lg"
          />
        </div>
        {isBookable ? (
          <span className="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
            Available
          </span>
        ) : (
          <span className="text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-300">
            Offline
          </span>
        )}
      </div>

      {!isBookable && (
        <div className="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs leading-relaxed" role="alert">
          <p className="font-semibold">Property Currently Offline</p>
          <p className="text-amber-800 text-[11px] mt-0.5">
            This property is not currently accepting new reservations.
          </p>
        </div>
      )}

      {/* Booking Form */}
      <form onSubmit={handleCheckAvailability} className="space-y-4">
        {/* Date Inputs Box */}
        <div className="rounded-2xl border border-slate-200 overflow-hidden bg-slate-50/50">
          <div className="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
            <div className="p-3">
              <label htmlFor={formIds.checkInId} className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                CHECK-IN
              </label>
              <input
                id={formIds.checkInId}
                type="date"
                value={checkIn}
                onChange={(e) => {
                  setCheckIn(e.target.value);
                  setValidationError(null);
                  setAvailabilityResult(null);
                }}
                min={formatDateForInput(new Date())}
                disabled={!isBookable}
                required
                className="w-full bg-transparent text-xs font-semibold text-slate-900 focus:outline-none cursor-pointer mt-1 disabled:opacity-50"
              />
            </div>
            <div className="p-3">
              <label htmlFor={formIds.checkOutId} className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                CHECKOUT
              </label>
              <input
                id={formIds.checkOutId}
                type="date"
                value={checkOut}
                onChange={(e) => {
                  setCheckOut(e.target.value);
                  setValidationError(null);
                  setAvailabilityResult(null);
                }}
                min={checkIn}
                disabled={!isBookable}
                required
                className="w-full bg-transparent text-xs font-semibold text-slate-900 focus:outline-none cursor-pointer mt-1 disabled:opacity-50"
              />
            </div>
          </div>

          {/* Guest Count */}
          <div className="p-3">
            <label htmlFor={formIds.guestsId} className="block text-[10px] font-bold uppercase tracking-wider text-slate-500">
              GUESTS
            </label>
            <select
              id={formIds.guestsId}
              value={guests}
              onChange={(e) => {
                setGuests(Number(e.target.value));
                setAvailabilityResult(null);
              }}
              disabled={!isBookable}
              className="w-full bg-transparent text-xs font-semibold text-slate-900 focus:outline-none cursor-pointer mt-1 disabled:opacity-50"
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
          disabled={!isBookable}
          className="w-full shadow-md font-semibold text-sm disabled:opacity-50"
        >
          {isBookable ? 'Check Availability' : 'Property Unavailable'}
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

          {availabilityResult.isAvailable && availabilityResult.nights && (
            <div className="space-y-1.5 border-t border-emerald-200/80 pt-3 text-slate-700">
              <div className="flex justify-between text-xs">
                <span>Requested duration:</span>
                <span className="font-semibold text-slate-900">
                  {availabilityResult.nights} {availabilityResult.nights === 1 ? 'night' : 'nights'}
                </span>
              </div>
              <p className="text-[11px] text-slate-500 pt-1">
                Final pricing and fees will be confirmed during reservation.
              </p>
            </div>
          )}

          {availabilityResult.isInformationalOnly && (
            <p className="text-[10px] text-slate-500 pt-1 border-t border-emerald-100">
              * Note: Availability check is informational. Dates and rates are confirmed during reservation.
            </p>
          )}
        </div>
      )}

      {/* Disclaimers */}
      <div className="text-center pt-2">
        <p className="text-[11px] text-slate-400">
          You won&apos;t be charged yet.
        </p>
      </div>
    </div>
  );
};
