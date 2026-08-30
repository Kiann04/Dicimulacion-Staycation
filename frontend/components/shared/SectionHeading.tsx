import React from 'react';
import { Badge } from '@/components/ui/Badge';
import { cn } from '@/lib/utils/cn';

export interface SectionHeadingProps {
  badge?: string;
  title: string;
  subtitle?: string;
  centered?: boolean;
  className?: string;
}

export const SectionHeading: React.FC<SectionHeadingProps> = ({
  badge,
  title,
  subtitle,
  centered = false,
  className,
}) => {
  return (
    <div
      className={cn(
        'space-y-2 mb-8',
        centered && 'text-center mx-auto max-w-2xl',
        className
      )}
    >
      {badge && (
        <Badge variant="neutral" size="sm" className="mb-1">
          {badge}
        </Badge>
      )}
      <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
        {title}
      </h2>
      {subtitle && (
        <p className="text-sm sm:text-base text-slate-600 leading-relaxed">
          {subtitle}
        </p>
      )}
    </div>
  );
};
