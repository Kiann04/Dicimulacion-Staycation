import { type ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatPHP(amount: number | string | undefined | null): string {
  if (amount === undefined || amount === null || isNaN(Number(amount))) {
    return '₱0.00';
  }
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(Number(amount));
}

export function formatDate(dateString: string | undefined | null): string {
  if (!dateString) return '';
  try {
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  } catch {
    return dateString;
  }
}

export function calculateBookingPrice(
  pricePerDay: number,
  startDateStr: string,
  endDateStr: string,
  guestNumber: number
) {
  const start = new Date(startDateStr);
  const end = new Date(endDateStr);
  
  const diffTime = end.getTime() - start.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  const nights = diffDays > 0 ? diffDays : 1;

  const basePrice = pricePerDay * nights;
  const extraGuests = Math.max(0, Number(guestNumber) - 6);
  const extraFee = extraGuests * 500;
  const totalPrice = basePrice + extraFee;
  const halfPayment = Math.round(totalPrice / 2);

  return {
    nights,
    basePrice,
    extraGuests,
    extraFee,
    totalPrice,
    halfPayment,
  };
}

export function getStatusBadgeVariant(status: string) {
  switch (status?.toLowerCase()) {
    case 'confirmed':
    case 'paid':
    case 'available':
      return 'emerald';
    case 'pending':
    case 'half_paid':
    case 'approved':
      return 'amber';
    case 'unpaid':
    case 'declined':
    case 'cancelled':
    case 'failed':
    case 'unavailable':
      return 'rose';
    case 'completed':
      return 'blue';
    default:
      return 'slate';
  }
}
