'use client';

import React, { useState, useEffect } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { StaycationFilter, StaycationSummary } from '@/lib/types/staycation';
import { staycationService } from '@/lib/api/staycation-service';
import { StaycationFilterBar } from './StaycationFilterBar';
import { StaycationGrid } from './StaycationGrid';
import { SectionHeading } from '@/components/shared/SectionHeading';
import { Container } from '@/components/ui/Container';

export interface StaycationListingClientProps {
  initialStaycations: StaycationSummary[];
}

export const StaycationListingClient: React.FC<StaycationListingClientProps> = ({
  initialStaycations,
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
    propertyType: undefined,
    sortBy: 'recommended',
  });

  const [staycations, setStaycations] = useState<StaycationSummary[]>(initialStaycations);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    let isMounted = true;

    async function loadData() {
      setIsLoading(true);
      try {
        const results = await staycationService.getStaycations(filters);
        if (isMounted) {
          setStaycations(results);
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    loadData();

    return () => {
      isMounted = false;
    };
  }, [filters]);

  const handleResetFilters = () => {
    setFilters({
      city: '',
      query: '',
      guests: undefined,
      propertyType: undefined,
      sortBy: 'recommended',
    });
    router.push('/staycations');
  };

  return (
    <Container size="lg" className="py-8 sm:py-12">
      <SectionHeading
        badge="Available Stays"
        title="Explore All Staycations"
        subtitle={`Discover ${staycations.length} exceptional vacation properties across top destinations.`}
      />

      <StaycationFilterBar
        filters={filters}
        onFilterChange={(newFilters) => setFilters(newFilters)}
        onReset={handleResetFilters}
      />

      <StaycationGrid
        staycations={staycations}
        isLoading={isLoading}
        emptyTitle="No staycations match your filter"
        emptyDescription="We couldn't find any properties matching all your filter conditions. Try loosening your search criteria."
        onResetFilters={handleResetFilters}
      />
    </Container>
  );
};
