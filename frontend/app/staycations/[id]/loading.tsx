import React from 'react';
import { Container } from '@/components/ui/Container';
import { LoadingState } from '@/components/shared/LoadingState';

export default function StaycationDetailLoading() {
  return (
    <Container size="lg" className="py-10">
      <LoadingState variant="skeleton-detail" />
    </Container>
  );
}
