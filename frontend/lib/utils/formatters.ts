/**
 * Formatting utilities for staycation UI displays.
 * Hardened to handle number, decimal string, null, and undefined inputs safely.
 */

export function formatCurrency(
  amount: number | string | null | undefined,
  currency = 'PHP'
): string {
  if (amount === null || amount === undefined || amount === '') return '₱0';
  const numericAmount = typeof amount === 'number' ? amount : Number(amount);
  if (isNaN(numericAmount)) return '₱0';

  if (currency === 'PHP') {
    return `₱${numericAmount.toLocaleString('en-PH', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    })}`;
  }

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
    maximumFractionDigits: 0,
  }).format(numericAmount);
}

export function formatGuestCount(guests?: number | null): string {
  const count = typeof guests === 'number' ? guests : 1;
  return `${count} ${count === 1 ? 'guest' : 'guests'}`;
}

export function formatBedroomCount(bedrooms?: number | null): string {
  if (bedrooms === 0) return 'Studio';
  const count = typeof bedrooms === 'number' ? bedrooms : 1;
  return `${count} ${count === 1 ? 'bedroom' : 'bedrooms'}`;
}

export function formatBathroomCount(bathrooms?: number | null): string {
  const count = typeof bathrooms === 'number' ? bathrooms : 1;
  return `${count} ${count === 1 ? 'bath' : 'baths'}`;
}

export function calculateNights(checkIn?: string, checkOut?: string): number {
  if (!checkIn || !checkOut) return 1;
  const start = new Date(checkIn);
  const end = new Date(checkOut);
  if (isNaN(start.getTime()) || isNaN(end.getTime())) return 1;
  const diffTime = end.getTime() - start.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays > 0 ? diffDays : 1;
}
