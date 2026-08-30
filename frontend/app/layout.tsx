import type { Metadata } from 'next';
import { Geist, Geist_Mono } from 'next/font/google';
import './globals.css';
import { Navbar } from '@/components/layout/Navbar';
import { Footer } from '@/components/layout/Footer';

const geistSans = Geist({
  variable: '--font-geist-sans',
  subsets: ['latin'],
});

const geistMono = Geist_Mono({
  variable: '--font-geist-mono',
  subsets: ['latin'],
});

export const metadata: Metadata = {
  title: {
    template: '%s | Dicimulacion Staycation',
    default: 'Dicimulacion Staycation | Premium Vacation Rentals & Getaways',
  },
  description:
    'Discover and book handpicked staycation retreats, beachfront villas, and luxury lofts across the Philippines.',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html
      lang="en"
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <body className="min-h-full flex flex-col bg-[#fafaf9] text-slate-900 selection:bg-slate-900 selection:text-white">
        <Navbar />
        <main className="flex-1 pb-16">{children}</main>
        <Footer />
      </body>
    </html>
  );
}
