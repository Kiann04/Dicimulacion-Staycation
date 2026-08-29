import React from "react";
import { Badge } from "@/components/ui/badge";
import { getStatusBadgeVariant } from "@/lib/utils";

interface StatusBadgeProps {
  status?: string | null;
  className?: string;
}

export function StatusBadge({ status, className }: StatusBadgeProps) {
  if (!status) return null;
  const variant = getStatusBadgeVariant(status);
  const formatted = status.replace(/_/g, " ");

  return (
    <Badge variant={variant as any} className={className}>
      {formatted}
    </Badge>
  );
}

interface EmptyStateProps {
  icon?: React.ReactNode;
  title: string;
  description?: string;
  action?: React.ReactNode;
  className?: string;
}

export function EmptyState({ icon, title, description, action, className }: EmptyStateProps) {
  return (
    <div className={`flex flex-col items-center justify-center text-center p-8 sm:p-12 rounded-2xl border border-dashed border-border/80 bg-muted/20 ${className || ""}`}>
      {icon && <div className="p-3 mb-3.5 rounded-2xl bg-muted/60 text-primary">{icon}</div>}
      <h4 className="font-serif text-lg font-medium text-foreground">{title}</h4>
      {description && <p className="mt-1 text-sm text-muted-foreground max-w-sm">{description}</p>}
      {action && <div className="mt-5">{action}</div>}
    </div>
  );
}

interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: React.ReactNode;
  trend?: {
    value: string;
    isPositive?: boolean;
  };
}

export function StatCard({ title, value, subtitle, icon, trend }: StatCardProps) {
  return (
    <div className="rounded-2xl border border-border/70 bg-card p-6 shadow-subtle hover:shadow-card transition-all duration-200">
      <div className="flex items-center justify-between">
        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{title}</p>
        <div className="rounded-xl bg-primary-50 dark:bg-primary-950/50 p-2.5 text-primary">{icon}</div>
      </div>
      <div className="mt-4 flex items-baseline justify-between">
        <h3 className="text-2xl sm:text-3xl font-serif font-bold text-foreground">{value}</h3>
        {trend && (
          <span
            className={`inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full ${
              trend.isPositive ? "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300" : "bg-rose-50 text-rose-700"
            }`}
          >
            {trend.value}
          </span>
        )}
      </div>
      {subtitle && <p className="mt-1.5 text-xs text-muted-foreground">{subtitle}</p>}
    </div>
  );
}
