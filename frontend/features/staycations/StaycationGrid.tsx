import React from 'react';
import { StaycationSummary } from '@/lib/types/staycation';
import { StaycationCard } from './StaycationCard';
import { LoadingState } from '@/components/shared/LoadingState';
import { EmptyState } from '@/components/shared/EmptyState';

export interface StaycationGridProps {
  staycations: StaycationSummary[];
  isLoading?: boolean;
  emptyTitle?: string;
  emptyDescription?: string;
  onResetFilters?: () => void;
}

export const StaycationGrid: React.FC<StaycationGridProps> = ({
  staycations,
  isLoading = false,
  emptyTitle,
  emptyDescription,
  onResetFilters,
}) => {
  if (isLoading) {
    return <LoadingState variant="skeleton-grid" />;
  }

  if (staycations.length === 0) {
    return (
      <EmptyState
        title={emptyTitle || 'No staycations match your search'}
        description={
          emptyDescription ||
          'Try clearing some filters or searching for another destination.'
        }
        onAction={onResetFilters}
        actionLabel="Reset Search Filters"
      />
    );
  }

  return (
    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      {staycations.map((staycation) => (
        <StaycationCard key={staycation.id} staycation={staycation} />
      ))}
    </div>
  );
};
