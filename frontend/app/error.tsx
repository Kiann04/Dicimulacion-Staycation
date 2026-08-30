'use client';

import React, { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { Container } from '@/components/ui/Container';
import { Button } from '@/components/ui/Button';

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  const router = useRouter();

  useEffect(() => {
    // Log client errors if necessary
    console.error('App error:', error);
  }, [error]);

  return (
    <Container size="sm" className="py-20 text-center space-y-6">
      <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 font-bold text-2xl border border-rose-200">
        !
      </div>
      <div className="space-y-2">
        <h1 className="text-2xl font-bold text-slate-900">
          Something went wrong
        </h1>
        <p className="text-sm text-slate-600 max-w-md mx-auto leading-relaxed">
          An unexpected error occurred while rendering this page.
        </p>
      </div>
      <div className="pt-2 flex justify-center gap-3">
        <Button variant="outline" size="md" onClick={() => reset()}>
          Try Again
        </Button>
        <Button variant="primary" size="md" onClick={() => router.push('/')}>
          Go Home
        </Button>
      </div>
    </Container>
  );
}
