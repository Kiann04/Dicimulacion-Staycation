import React from "react";
import { PropertyCardSkeleton } from "@/components/feedback/LoadingSkeleton";

export default function Loading() {
  return (
    <div className="container mx-auto py-16 px-6 max-w-6xl space-y-8 animate-in fade-in duration-300">
      <div className="h-8 w-64 bg-muted/70 rounded-xl animate-pulse" />
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <PropertyCardSkeleton />
        <PropertyCardSkeleton />
        <PropertyCardSkeleton />
      </div>
    </div>
  );
}
