'use client';

import React, { useState, useEffect } from 'react';
import Image from 'next/image';
import { StaycationImage } from '@/lib/types/staycation';

export interface StaycationGalleryProps {
  images?: StaycationImage[];
  title?: string;
}

export const StaycationGallery: React.FC<StaycationGalleryProps> = ({ images, title = 'Staycation' }) => {
  const [activeModalImage, setActiveModalImage] = useState<StaycationImage | null>(null);

  // Close modal on Escape key and prevent background body scrolling
  useEffect(() => {
    if (!activeModalImage) return;

    const originalOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setActiveModalImage(null);
      }
    };

    window.addEventListener('keydown', handleKeyDown);

    return () => {
      document.body.style.overflow = originalOverflow;
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [activeModalImage]);

  if (!images || images.length === 0) {
    return (
      <div className="aspect-16/9 w-full bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-sm">
        No images available for this property.
      </div>
    );
  }

  const primaryImage = images[0];
  const sideImages = images.slice(1, 5);

  return (
    <div className="space-y-3">
      {/* Gallery Grid */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-2.5 sm:gap-3 rounded-2xl sm:rounded-3xl overflow-hidden aspect-4/3 md:aspect-21/9 max-h-[520px]">
        {/* Main large image */}
        <div
          role="button"
          tabIndex={0}
          onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && setActiveModalImage(primaryImage)}
          className="relative md:col-span-2 h-full w-full bg-slate-100 cursor-pointer overflow-hidden group focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 rounded-l-2xl sm:rounded-l-3xl"
          onClick={() => setActiveModalImage(primaryImage)}
          aria-label={`View photo 1: ${primaryImage.alt || title}`}
        >
          <Image
            src={primaryImage.url}
            alt={primaryImage.alt || title}
            fill
            priority
            sizes="(max-width: 768px) 100vw, 50vw"
            className="object-cover group-hover:scale-103 transition-transform duration-500"
          />
          <div className="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-colors" />
        </div>

        {/* 4-grid side thumbnails (hidden on mobile, shown md+) */}
        <div className="hidden md:grid col-span-2 grid-cols-2 gap-2.5 sm:gap-3 h-full">
          {sideImages.map((img, idx) => (
            <div
              key={String(img.id)}
              role="button"
              tabIndex={0}
              onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && setActiveModalImage(img)}
              className="relative h-full w-full bg-slate-100 cursor-pointer overflow-hidden group focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900"
              onClick={() => setActiveModalImage(img)}
              aria-label={`View photo ${idx + 2}: ${img.alt || `${title} photo ${idx + 2}`}`}
            >
              <Image
                src={img.url}
                alt={img.alt || `${title} photo ${idx + 2}`}
                fill
                sizes="25vw"
                className="object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <div className="absolute inset-0 bg-black/10 group-hover:bg-black/0 transition-colors" />
            </div>
          ))}
        </div>
      </div>

      {/* Gallery footer hint */}
      <div className="flex justify-between items-center text-xs text-slate-500 px-1">
        <span>Showing {images.length} verified property photographs</span>
        <button
          type="button"
          onClick={() => setActiveModalImage(primaryImage)}
          className="font-medium text-slate-900 hover:underline cursor-pointer focus:outline-none focus-visible:ring-1 focus-visible:ring-slate-900 rounded"
        >
          View all photos ↗
        </button>
      </div>

      {/* Fullscreen Modal View */}
      {activeModalImage && (
        <div
          role="dialog"
          aria-modal="true"
          aria-label="Property photograph preview"
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-xs animate-in fade-in duration-200"
          onClick={() => setActiveModalImage(null)}
        >
          <div
            className="relative max-w-5xl w-full max-h-[90vh] flex flex-col items-center"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              type="button"
              onClick={() => setActiveModalImage(null)}
              className="absolute -top-12 right-0 text-white hover:text-slate-300 font-semibold text-sm px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
              aria-label="Close photo preview"
            >
              ✕ Close (Esc)
            </button>
            <div className="relative w-full aspect-16/10 rounded-xl overflow-hidden shadow-2xl bg-black">
              <Image
                src={activeModalImage.url}
                alt={activeModalImage.alt || title}
                fill
                sizes="90vw"
                className="object-contain"
              />
            </div>
            {activeModalImage.alt && (
              <p className="mt-3 text-xs sm:text-sm text-slate-300 text-center max-w-lg">
                {activeModalImage.alt}
              </p>
            )}
          </div>
        </div>
      )}
    </div>
  );
};
