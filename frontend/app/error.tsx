"use client";

import React, { useEffect } from "react";
import { ErrorState } from "@/components/feedback/ErrorState";

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error("Application error:", error);
  }, [error]);

  return (
    <div className="min-h-[70vh] flex items-center justify-center p-6">
      <ErrorState
        title="We encountered an unexpected error"
        message={error.message || "Failed to load staycation application content."}
        onRetry={() => reset()}
      />
    </div>
  );
}
