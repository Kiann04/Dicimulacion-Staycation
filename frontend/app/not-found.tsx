import React from 'react';
import Link from 'next/link';
import { Container } from '@/components/ui/Container';
import { Button } from '@/components/ui/Button';

export default function NotFound() {
  return (
    <Container size="sm" className="py-20 text-center space-y-6">
      <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 font-bold text-3xl">
        404
      </div>
      <div className="space-y-2">
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
          Page Not Found
        </h1>
        <p className="text-sm text-slate-600 max-w-md mx-auto leading-relaxed">
          The staycation or page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
      </div>
      <div className="pt-2">
        <Link href="/">
          <Button variant="primary" size="md">
            Return to Homepage
          </Button>
        </Link>
      </div>
    </Container>
  );
}
