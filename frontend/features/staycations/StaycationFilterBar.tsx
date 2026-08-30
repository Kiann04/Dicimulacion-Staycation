'use client';

import React from 'react';
import { StaycationFilter } from '@/lib/types/staycation';

export interface StaycationFilterBarProps {
  filters: StaycationFilter;
  onFilterChange: (newFilters: StaycationFilter) => void;
  onReset: () => void;
}

const CITIES = [
  { label: 'All Destinations', value: '' },
  { label: 'Tagaytay', value: 'Tagaytay' },
  { label: 'Makati', value: 'Makati' },
  { label: 'Calatagan, Batangas', value: 'Calatagan' },
  { label: 'Baguio City', value: 'Baguio City' },
  { label: 'General Luna, Siargao', value: 'General Luna' },
  { label: 'Cebu City', value: 'Cebu City' },
];

export const StaycationFilterBar: React.FC<StaycationFilterBarProps> = ({
  filters,
  onFilterChange,
  onReset,
}) => {
  const hasActiveFilters = Boolean(
    filters.query || filters.city || filters.guests || (filters.sortBy && filters.sortBy !== 'recommended')
  );

  return (
    <div className="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs mb-8 space-y-4">
      {/* Top Search Controls */}
      <div className="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        {/* Search Keyword */}
        <div className="relative">
          <label htmlFor="search-input" className="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">
            Search
          </label>
          <div className="relative">
            <input
              id="search-input"
              type="text"
              placeholder="Search by name or feature..."
              value={filters.query || ''}
              onChange={(e) => onFilterChange({ ...filters, query: e.target.value })}
              className="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900"
            />
            {filters.query && (
              <button
                type="button"
                onClick={() => onFilterChange({ ...filters, query: '' })}
                className="absolute right-2.5 top-2.5 text-xs text-slate-400 hover:text-slate-600 cursor-pointer"
                aria-label="Clear search query"
              >
                ✕
              </button>
            )}
          </div>
        </div>

        {/* Destination Dropdown */}
        <div>
          <label htmlFor="city-select" className="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">
            Destination
          </label>
          <select
            id="city-select"
            value={filters.city || ''}
            onChange={(e) => onFilterChange({ ...filters, city: e.target.value })}
            className="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 cursor-pointer"
          >
            {CITIES.map((c) => (
              <option key={c.value} value={c.value}>
                {c.label}
              </option>
            ))}
          </select>
        </div>

        {/* Guests Filter */}
        <div>
          <label htmlFor="guests-select" className="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">
            Guests
          </label>
          <select
            id="guests-select"
            value={filters.guests || ''}
            onChange={(e) =>
              onFilterChange({
                ...filters,
                guests: e.target.value ? Number(e.target.value) : undefined,
              })
            }
            className="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 cursor-pointer"
          >
            <option value="">Any number of guests</option>
            <option value="2">2+ Guests</option>
            <option value="4">4+ Guests</option>
            <option value="6">6+ Guests</option>
            <option value="8">8+ Guests</option>
          </select>
        </div>

        {/* Sort Order */}
        <div>
          <label htmlFor="sort-select" className="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">
            Sort By
          </label>
          <select
            id="sort-select"
            value={filters.sortBy || 'recommended'}
            onChange={(e) =>
              onFilterChange({
                ...filters,
                sortBy: e.target.value as StaycationFilter['sortBy'],
              })
            }
            className="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 cursor-pointer"
          >
            <option value="recommended">Default</option>
            <option value="rating">Highest Rated</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
          </select>
        </div>
      </div>

      {/* Reset Action if active */}
      {hasActiveFilters && (
        <div className="pt-2 border-t border-slate-100 flex items-center justify-end">
          <button
            type="button"
            onClick={onReset}
            className="text-xs text-slate-500 hover:text-slate-900 underline underline-offset-2 font-medium cursor-pointer"
          >
            Clear all filters
          </button>
        </div>
      )}
    </div>
  );
};
