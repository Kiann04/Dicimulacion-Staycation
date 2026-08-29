import { z } from "zod";

export const loginSchema = z.object({
  email: z.string().email("Please enter a valid email address."),
  password: z.string().min(6, "Password must be at least 6 characters."),
  remember: z.boolean().optional(),
});

export const registerSchema = z.object({
  name: z.string().min(2, "Full name is required (min 2 characters)."),
  email: z.string().email("Please enter a valid email address."),
  password: z.string().min(8, "Password must be at least 8 characters."),
  password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords do not match.",
  path: ["password_confirmation"],
});

export const twoFactorSchema = z.object({
  code: z.string().min(6, "Code must be 6 digits.").max(6, "Code must be 6 digits."),
});

export const bookingPreviewSchema = z.object({
  name: z.string().min(2, "Full name is required."),
  phone: z.string().min(10, "Please enter a valid Philippine mobile number (e.g. 09171234567)."),
  guest_number: z.coerce.number().min(1, "At least 1 guest is required.").max(20, "Maximum 20 guests."),
  startDate: z.string().min(1, "Check-in date is required."),
  endDate: z.string().min(1, "Check-out date is required."),
}).refine((data) => {
  if (!data.startDate || !data.endDate) return true;
  return new Date(data.endDate) > new Date(data.startDate);
}, {
  message: "Check-out date must be after check-in date.",
  path: ["endDate"],
});

export const bookingSubmitSchema = z.object({
  startDate: z.string().min(1, "Check-in date is required."),
  endDate: z.string().min(1, "Check-out date is required."),
  guest_number: z.coerce.number().min(1),
  name: z.string().min(2),
  phone: z.string().min(10),
  payment_type: z.enum(["half", "full"]),
  payment_method: z.enum(["gcash", "bpi"]),
  transaction_number: z.string().optional(),
  message: z.string().max(500, "Message cannot exceed 500 characters.").optional(),
});

export const reviewSchema = z.object({
  booking_id: z.number().int().positive(),
  rating: z.number().min(1, "Please select at least 1 star.").max(5),
  comment: z.string().min(5, "Review comment must be at least 5 characters.").max(1000, "Review cannot exceed 1000 characters."),
});

export const inquirySchema = z.object({
  email: z.string().email("Please enter a valid email address."),
  message: z.string().min(10, "Message must be at least 10 characters.").max(2000),
});

export const staycationFormSchema = z.object({
  house_name: z.string().min(3, "Property name is required."),
  house_description: z.string().min(10, "Description must be at least 10 characters."),
  house_price: z.coerce.number().min(500, "Price per night must be at least ₱500."),
  house_location: z.string().min(3, "Location is required."),
  house_availability: z.enum(["available", "unavailable"]),
});

export const blockedDateSchema = z.object({
  staycation_id: z.coerce.number().int().positive("Please select a property."),
  start_date: z.string().min(1, "Start date is required."),
  end_date: z.string().min(1, "End date is required."),
  reason: z.string().max(255).optional(),
}).refine((data) => {
  if (!data.start_date || !data.end_date) return true;
  return new Date(data.end_date) >= new Date(data.start_date);
}, {
  message: "End date must be on or after start date.",
  path: ["end_date"],
});

export const profileUpdateSchema = z.object({
  name: z.string().min(2, "Name is required."),
  email: z.string().email("Valid email is required."),
});

export const passwordUpdateSchema = z.object({
  current_password: z.string().min(6, "Current password is required."),
  password: z.string().min(8, "New password must be at least 8 characters."),
  password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords do not match.",
  path: ["password_confirmation"],
});
