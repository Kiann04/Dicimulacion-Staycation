import React from 'react';
import { Skeleton } from '@/components/ui/Skeleton';

export interface LoadingStateProps {
  message?: string;
  variant?: 'spinner' | 'skeleton-grid' | 'skeleton-detail';
}

export const LoadingState: React.FC<LoadingStateProps> = ({
  message = 'Loading staycations...',
  variant = 'spinner',
}) => {
  if (variant === 'skeleton-grid') {
    return (
      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {[1, 2, 3, 4, 5, 6].map((i) => (
          <div key={i} className="flex flex-col gap-3 rounded-2xl bg-white p-3 border border-slate-100 shadow-sm">
            <Skeleton className="aspect-4/3 w-full rounded-xl" />
            <div className="space-y-2 p-1">
              <div className="flex justify-between items-center">
                <Skeleton className="h-5 w-3/5" />
                <Skeleton className="h-4 w-12" />
              </div>
              <Skeleton className="h-4 w-4/5" />
              <div className="pt-2 flex justify-between items-center">
                <Skeleton className="h-6 w-28" />
                <Skeleton className="h-8 w-24 rounded-lg" />
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (variant === 'skeleton-detail') {
    return (
      <div className="space-y-8 animate-pulse">
        <div className="space-y-3">
          <Skeleton className="h-8 w-3/4 max-w-xl" />
          <Skeleton className="h-4 w-1/3" />
        </div>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 h-[420px]">
          <Skeleton className="md:col-span-2 h-full rounded-2xl" />
          <div className="hidden md:grid col-span-2 grid-cols-2 gap-4 h-full">
            <Skeleton className="h-full rounded-2xl" />
            <Skeleton className="h-full rounded-2xl" />
            <Skeleton className="h-full rounded-2xl" />
            <Skeleton className="h-full rounded-2xl" />
          </div>
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
          <div className="lg:col-span-2 space-y-6">
            <Skeleton className="h-24 w-full rounded-2xl" />
            <Skeleton className="h-48 w-full rounded-2xl" />
          </div>
          <div className="lg:col-span-1">
            <Skeleton className="h-96 w-full rounded-2xl" />
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center justify-center py-16 px-4 text-center">
      <div className="h-10 w-10 animate-spin rounded-full border-3 border-slate-200 border-t-slate-900 mb-4" />
      <p className="text-sm font-medium text-slate-600">{message}</p>
    </div>
  );
};
