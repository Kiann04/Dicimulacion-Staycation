import React, { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils/cn';

export interface SkeletonProps extends HTMLAttributes<HTMLDivElement> {
  variant?: 'rectangular' | 'rounded' | 'circular';
}

export const Skeleton: React.FC<SkeletonProps> = ({
  className,
  variant = 'rounded',
  ...props
}) => {
  const variants = {
    rectangular: 'rounded-none',
    rounded: 'rounded-xl',
    circular: 'rounded-full',
  };

  return (
    <div
      className={cn(
        'animate-pulse bg-slate-200/80',
        variants[variant],
        className
      )}
      aria-hidden="true"
      {...props}
    />
  );
};
