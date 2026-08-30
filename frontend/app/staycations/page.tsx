import React, { Suspense } from 'react';
import type { Metadata } from 'next';
import { staycationService } from '@/lib/api/staycation-service';
import { StaycationListingClient } from '@/features/staycations/StaycationListingClient';
import { LoadingState } from '@/components/shared/LoadingState';
import { Container } from '@/components/ui/Container';
import { StaycationPaginationMeta, StaycationSummary } from '@/lib/types/staycation';

export const metadata: Metadata = {
  title: 'Explore Staycations',
  description: 'Browse vacation rentals and private getaways.',
};

export default async function StaycationsPage() {
  let initialStaycations: StaycationSummary[] = [];
  let initialMeta: StaycationPaginationMeta | undefined;
  let initialError: string | null = null;

  try {
    const collectionRes = await staycationService.getStaycationCollection();
    initialStaycations = collectionRes.items;
    initialMeta = collectionRes.meta;
  } catch (err) {
    initialError =
      err instanceof Error
        ? err.message
        : 'Unable to connect to the staycation service. Please verify the API is reachable.';
  }

  return (
    <Suspense
      fallback={
        <Container size="lg" className="py-12">
          <LoadingState variant="skeleton-grid" />
        </Container>
      }
    >
      <StaycationListingClient
        initialStaycations={initialStaycations}
        initialMeta={initialMeta}
        initialError={initialError}
      />
    </Suspense>
  );
}
