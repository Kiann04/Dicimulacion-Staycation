import { z } from "zod";

export const dateGuestSchema = z
  .object({
    startDate: z.string().min(1, "Please select a check-in date"),
    endDate: z.string().min(1, "Please select a check-out date"),
    guestNumber: z.number().min(1, "At least 1 guest required").max(30, "Max 30 guests"),
  })
  .refine((data) => new Date(data.endDate) > new Date(data.startDate), {
    message: "Check-out date must be after check-in date",
    path: ["endDate"],
  });

export const guestDetailsSchema = z.object({
  name: z.string().min(2, "Full name must be at least 2 characters"),
  email: z.string().email("Please enter a valid email address"),
  phone: z.string().min(10, "Please enter a valid contact number (e.g. 09171234567)"),
  specialRequests: z.string().optional(),
});

export const paymentSelectionSchema = z.object({
  paymentMethod: z.enum(["gcash", "bpi"], {
    required_error: "Please select a payment method",
  }),
  paymentOption: z.enum(["half", "full"], {
    required_error: "Please select full or 50% downpayment",
  }),
});
