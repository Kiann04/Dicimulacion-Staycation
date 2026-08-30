'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { Container } from '@/components/ui/Container';
import { Button } from '@/components/ui/Button';
import { cn } from '@/lib/utils/cn';

export const Navbar: React.FC = () => {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const pathname = usePathname();

  const navLinks = [
    { href: '/', label: 'Home' },
    { href: '/staycations', label: 'Explore Staycations' },
  ];

  // Close mobile drawer on Escape key press
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isMobileMenuOpen) {
        setIsMobileMenuOpen(false);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isMobileMenuOpen]);

  return (
    <header className="sticky top-0 z-40 w-full border-b border-slate-200/80 bg-white/95 backdrop-blur-md transition-all">
      <Container size="lg">
        <div className="flex h-20 items-center justify-between">
          {/* Brand Logo */}
          <Link
            href="/"
            className="flex items-center gap-3 group focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 rounded-lg p-1"
            aria-label="Dicimulacion Staycation Home"
          >
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white font-bold tracking-wider text-lg shadow-sm group-hover:bg-slate-800 transition-colors">
              D
            </div>
            <div className="flex flex-col">
              <span className="text-base font-bold tracking-tight text-slate-900">
                Dicimulacion
              </span>
              <span className="text-[11px] font-medium tracking-widest uppercase text-slate-500">
                Staycation
              </span>
            </div>
          </Link>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center gap-1" aria-label="Main Navigation">
            {navLinks.map((link) => {
              const isActive =
                link.href === '/'
                  ? pathname === '/'
                  : pathname.startsWith(link.href);
              return (
                <Link
                  key={link.href}
                  href={link.href}
                  className={cn(
                    'px-4 py-2 rounded-xl text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900',
                    isActive
                      ? 'text-slate-900 bg-slate-100/80 font-semibold'
                      : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                  )}
                >
                  {link.label}
                </Link>
              );
            })}
          </nav>

          {/* Desktop Action Buttons */}
          <div className="hidden md:flex items-center gap-3">
            <Link href="/staycations">
              <Button size="md" variant="primary">
                Browse Properties
              </Button>
            </Link>
          </div>

          {/* Mobile Hamburger Button */}
          <button
            type="button"
            className="md:hidden flex items-center justify-center min-h-[44px] min-w-[44px] rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            aria-expanded={isMobileMenuOpen}
            aria-controls="mobile-navigation-drawer"
            aria-label="Toggle navigation menu"
          >
            {isMobileMenuOpen ? (
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            ) : (
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            )}
          </button>
        </div>

        {/* Mobile Navigation Drawer */}
        {isMobileMenuOpen && (
          <div
            id="mobile-navigation-drawer"
            className="md:hidden border-t border-slate-100 py-4 px-2 space-y-2 animate-in fade-in slide-in-from-top-2 duration-150"
          >
            {navLinks.map((link) => {
              const isActive =
                link.href === '/'
                  ? pathname === '/'
                  : pathname.startsWith(link.href);
              return (
                <Link
                  key={link.href}
                  href={link.href}
                  onClick={() => setIsMobileMenuOpen(false)}
                  className={cn(
                    'block px-4 py-3 rounded-xl text-base font-medium transition-colors',
                    isActive
                      ? 'text-slate-900 bg-slate-100 font-semibold'
                      : 'text-slate-600 hover:bg-slate-50'
                  )}
                >
                  {link.label}
                </Link>
              );
            })}
            <div className="pt-2">
              <Link
                href="/staycations"
                onClick={() => setIsMobileMenuOpen(false)}
                className="w-full block"
              >
                <Button size="lg" variant="primary" className="w-full">
                  Browse Properties
                </Button>
              </Link>
            </div>
          </div>
        )}
      </Container>
    </header>
  );
};
