import React from "react";
import { Skeleton } from "@/components/ui/skeleton";

export function PropertyCardSkeleton() {
  return (
    <div className="rounded-2xl border border-border/80 bg-card overflow-hidden shadow-subtle flex flex-col animate-pulse">
      <div className="h-64 bg-muted" />
      <div className="p-6 space-y-4">
        <div className="h-4 w-24 bg-muted rounded" />
        <div className="h-6 w-48 bg-muted rounded" />
        <div className="space-y-2">
          <div className="h-3 w-full bg-muted rounded" />
          <div className="h-3 w-3/4 bg-muted rounded" />
        </div>
        <div className="h-10 w-full bg-muted rounded-xl mt-4" />
      </div>
    </div>
  );
}

export function TableRowSkeleton({ cols = 6 }: { cols?: number }) {
  return (
    <tr className="animate-pulse">
      {[...Array(cols)].map((_, idx) => (
        <td key={idx} className="p-4">
          <div className="h-4 bg-muted rounded w-full" />
        </td>
      ))}
    </tr>
  );
}
