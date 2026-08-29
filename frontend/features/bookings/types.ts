export type BookingStep = "dates" | "guests" | "payment" | "confirmed";

export interface BookingFormState {
  startDate: string;
  endDate: string;
  guestNumber: number;
  name: string;
  email: string;
  phone: string;
  specialRequests?: string;
  paymentMethod: "gcash" | "bpi";
  paymentOption: "half" | "full";
  proofFile: File | null;
}

export interface AuthoritativePriceSummary {
  nights: number;
  basePrice: number;
  extraGuests: number;
  extraFee: number;
  totalPrice: number;
  halfPayment: number;
  fullPayment: number;
}
