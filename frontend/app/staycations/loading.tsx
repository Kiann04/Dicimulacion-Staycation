import React from 'react';
import { Container } from '@/components/ui/Container';
import { LoadingState } from '@/components/shared/LoadingState';

export default function StaycationsLoading() {
  return (
    <Container size="lg" className="py-12">
      <div className="space-y-8">
        <div className="space-y-2">
          <div className="h-6 w-32 bg-slate-200 animate-pulse rounded-full" />
          <div className="h-10 w-64 bg-slate-200 animate-pulse rounded-xl" />
        </div>
        <LoadingState variant="skeleton-grid" />
      </div>
    </Container>
  );
}
