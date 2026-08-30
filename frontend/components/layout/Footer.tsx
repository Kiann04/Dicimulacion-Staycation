import React from 'react';
import Link from 'next/link';
import { Container } from '@/components/ui/Container';

export const Footer: React.FC = () => {
  return (
    <footer className="border-t border-slate-200 bg-slate-900 text-slate-300">
      <Container size="lg" className="py-14 sm:py-16">
        <div className="grid grid-cols-1 gap-10 sm:grid-cols-2 md:grid-cols-4 lg:gap-12">
          {/* Brand Col */}
          <div className="space-y-4 sm:col-span-2 md:col-span-1">
            <Link href="/" className="flex items-center gap-3">
              <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-slate-900 font-bold text-base">
                D
              </div>
              <div className="flex flex-col">
                <span className="text-base font-bold text-white">Dicimulacion</span>
                <span className="text-[10px] uppercase tracking-widest text-slate-400">
                  Staycation
                </span>
              </div>
            </Link>
            <p className="text-sm text-slate-400 leading-relaxed max-w-sm">
              Vacation sanctuaries across the Philippines. Crafted for memorable weekend retreats, workcations, and family gatherings.
            </p>
          </div>

          {/* Quick Links */}
          <div className="space-y-3">
            <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
              Explore
            </h3>
            <ul className="space-y-2.5 text-sm">
              <li>
                <Link href="/staycations" className="text-slate-400 hover:text-white transition-colors">
                  All Staycations
                </Link>
              </li>
              <li>
                <Link href="/staycations?city=Tagaytay" className="text-slate-400 hover:text-white transition-colors">
                  Tagaytay
                </Link>
              </li>
              <li>
                <Link href="/staycations?city=Makati" className="text-slate-400 hover:text-white transition-colors">
                  Makati
                </Link>
              </li>
              <li>
                <Link href="/staycations?city=Calatagan" className="text-slate-400 hover:text-white transition-colors">
                  Batangas
                </Link>
              </li>
            </ul>
          </div>

          {/* Trust & Support */}
          <div className="space-y-3">
            <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
              Trust & Support
            </h3>
            <ul className="space-y-2.5 text-sm text-slate-400">
              <li className="flex items-center gap-2">
                <svg className="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
                <span>Staycation Directory</span>
              </li>
              <li className="flex items-center gap-2">
                <svg className="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
                <span>Direct Host Communication</span>
              </li>
              <li className="flex items-center gap-2">
                <svg className="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
                <span>Transparent Pricing</span>
              </li>
            </ul>
          </div>

          {/* Contact & Hours */}
          <div className="space-y-3">
            <h3 className="text-xs font-semibold uppercase tracking-wider text-white">
              Guest Support
            </h3>
            <p className="text-sm text-slate-400">
              Customer assistance available 7 days a week from 8:00 AM to 9:00 PM PHT.
            </p>
            <div className="pt-1">
              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-800 text-emerald-400 border border-slate-700">
                ● Support Online
              </span>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="mt-12 pt-8 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
          <p>© {new Date().getFullYear()} Dicimulacion Staycation. All rights reserved.</p>
          <div className="flex gap-6">
            <span className="hover:text-slate-400 cursor-pointer">Privacy Policy</span>
            <span className="hover:text-slate-400 cursor-pointer">Terms of Service</span>
            <span className="hover:text-slate-400 cursor-pointer">Guest Guidelines</span>
          </div>
        </div>
      </Container>
    </footer>
  );
};
