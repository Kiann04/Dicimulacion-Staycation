import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import { staycationService } from '@/lib/api/staycation-service';
import { StaycationListingClient } from '@/features/staycations/StaycationListingClient';
import { LoadingState } from '@/components/shared/LoadingState';
import { Container } from '@/components/ui/Container';

export const metadata: Metadata = {
  title: 'Explore Staycations',
  description: 'Browse luxury villas, modern lofts, and cozy chalets for your next staycation.',
};

export default async function StaycationsPage() {
  const initialStaycations = await staycationService.getStaycations();

  return (
    <Suspense
      fallback={
        <Container size="lg" className="py-12">
          <LoadingState variant="skeleton-grid" />
        </Container>
      }
    >
      <StaycationListingClient initialStaycations={initialStaycations} />
    </Suspense>
  );
}
