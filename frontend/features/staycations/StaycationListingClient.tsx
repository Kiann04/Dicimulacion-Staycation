'use client';

import React, { useState, useMemo } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { StaycationFilter, StaycationPaginationMeta, StaycationSummary } from '@/lib/types/staycation';
import { staycationService } from '@/lib/api/staycation-service';
import { StaycationFilterBar } from './StaycationFilterBar';
import { StaycationGrid } from './StaycationGrid';
import { SectionHeading } from '@/components/shared/SectionHeading';
import { ErrorState } from '@/components/shared/ErrorState';
import { Container } from '@/components/ui/Container';

export interface StaycationListingClientProps {
  initialStaycations: StaycationSummary[];
  initialMeta?: StaycationPaginationMeta;
  initialError?: string | null;
}

export const StaycationListingClient: React.FC<StaycationListingClientProps> = ({
  initialStaycations,
  initialMeta,
  initialError = null,
}) => {
  const searchParams = useSearchParams();
  const router = useRouter();

  const cityParam = searchParams.get('city') || '';
  const queryParam = searchParams.get('query') || '';
  const guestsParam = searchParams.get('guests') ? Number(searchParams.get('guests')) : undefined;

  const [filters, setFilters] = useState<StaycationFilter>({
    city: cityParam,
    query: queryParam,
    guests: guestsParam,
    sortBy: 'recommended',
  });

  const [allStaycations, setAllStaycations] = useState<StaycationSummary[]>(initialStaycations);
  const [meta, setMeta] = useState<StaycationPaginationMeta | undefined>(initialMeta);
  const [isLoading, setIsLoading] = useState(false);
  const [fetchError, setFetchError] = useState<string | null>(initialError);

  // Pure in-memory filtering over loaded dataset
  const filteredStaycations = useMemo(() => {
    let list = [...allStaycations];

    if (filters.query) {
      const q = filters.query.toLowerCase().trim();
      list = list.filter(
        (item) =>
          item.title.toLowerCase().includes(q) ||
          (item.description && item.description.toLowerCase().includes(q)) ||
          (item.location?.city && item.location.city.toLowerCase().includes(q))
      );
    }

    if (filters.city) {
      const cityLower = filters.city.toLowerCase().trim();
      list = list.filter(
        (item) => item.location?.city?.toLowerCase() === cityLower
      );
    }

    if (filters.guests) {
      list = list.filter((item) => (item.maxGuests ?? 1) >= (filters.guests || 1));
    }

    if (filters.minPrice) {
      list = list.filter((item) => Number(item.pricePerNight) >= (filters.minPrice || 0));
    }

    if (filters.maxPrice) {
      list = list.filter((item) => Number(item.pricePerNight) <= (filters.maxPrice || Infinity));
    }

    if (filters.sortBy) {
      if (filters.sortBy === 'price_asc') {
        list.sort((a, b) => Number(a.pricePerNight) - Number(b.pricePerNight));
      } else if (filters.sortBy === 'price_desc') {
        list.sort((a, b) => Number(b.pricePerNight) - Number(a.pricePerNight));
      } else if (filters.sortBy === 'rating') {
        list.sort((a, b) => (b.reviews?.rating ?? 0) - (a.reviews?.rating ?? 0));
      }
    }

    return list;
  }, [allStaycations, filters]);

  const handleRefresh = async () => {
    setIsLoading(true);
    setFetchError(null);
    try {
      const collectionRes = await staycationService.getStaycationCollection();
      setAllStaycations(collectionRes.items);
      setMeta(collectionRes.meta);
    } catch (err) {
      setFetchError(
        err instanceof Error
          ? err.message
          : 'Unable to connect to the staycation service. Please verify the API is reachable.'
      );
    } finally {
      setIsLoading(false);
    }
  };

  const handleResetFilters = () => {
    setFilters({
      city: '',
      query: '',
      guests: undefined,
      sortBy: 'recommended',
    });
    router.push('/staycations');
  };

  const totalCount = meta?.total ?? allStaycations.length;

  return (
    <Container size="lg" className="py-8 sm:py-12">
      <SectionHeading
        badge="Catalogue"
        title="Staycations"
        subtitle={
          fetchError
            ? 'Catalogue temporarily unavailable'
            : `Showing ${filteredStaycations.length} of ${totalCount} listed properties.`
        }
      />

      <StaycationFilterBar
        filters={filters}
        onFilterChange={(newFilters) => setFilters(newFilters)}
        onReset={handleResetFilters}
      />

      <div aria-live="polite" className="sr-only">
        {isLoading
          ? 'Loading staycations...'
          : `Showing ${filteredStaycations.length} of ${totalCount} properties.`}
      </div>

      {fetchError ? (
        <ErrorState
          title="Could not load staycations"
          message={fetchError}
          onRetry={handleRefresh}
          retryLabel="Retry loading stays"
        />
      ) : (
        <div aria-busy={isLoading}>
          <StaycationGrid
            staycations={filteredStaycations}
            isLoading={isLoading}
            emptyTitle="No staycations match your filter"
            emptyDescription="We couldn't find any properties matching all your filter conditions. Try loosening your search criteria."
            onResetFilters={handleResetFilters}
          />
        </div>
      )}
    </Container>
  );
};
