import React from 'react';
import { StaycationAmenity } from '@/lib/types/staycation';

export interface StaycationAmenitiesListProps {
  amenities?: StaycationAmenity[];
}

export const StaycationAmenitiesList: React.FC<StaycationAmenitiesListProps> = ({
  amenities = [],
}) => {
  if (!amenities || amenities.length === 0) {
    return (
      <div className="space-y-2">
        <h3 className="text-lg font-bold text-slate-900">What this stay offers</h3>
        <p className="text-xs text-slate-500">Contact host for specific amenity information.</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h3 className="text-lg font-bold text-slate-900">What this stay offers</h3>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
        {amenities.map((amenity) => (
          <div
            key={String(amenity.id)}
            className="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50 text-slate-800"
          >
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-700 shadow-2xs">
              <svg className="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div className="flex flex-col">
              <span className="text-sm font-medium text-slate-900">{amenity.name}</span>
              {amenity.category && (
                <span className="text-[11px] capitalize text-slate-400">{amenity.category}</span>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};
