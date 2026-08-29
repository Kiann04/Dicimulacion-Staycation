"use client";

import React, { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Staycation } from "@/types";
import { bookingService } from "@/lib/services/bookingService";
import { authService } from "@/lib/services/authService";
import { ApiError } from "@/lib/api/client";
import { formatPHP } from "@/lib/utils";
import { Dialog } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { DateGuestSelector } from "./DateGuestSelector";
import { PricingBreakdown } from "./PricingBreakdown";
import { GuestDetailsForm } from "./GuestDetailsForm";
import { PaymentSelection } from "./PaymentSelection";
import { BookingStep } from "../types";
import { ArrowRight, ArrowLeft, Lock } from "lucide-react";

interface BookingModalProps {
  staycation: Staycation;
  isOpen: boolean;
  onClose: () => void;
  initialStartDate?: string;
  initialEndDate?: string;
  initialGuests?: number;
}

export function BookingModal({
  staycation,
  isOpen,
  onClose,
  initialStartDate = "",
  initialEndDate = "",
  initialGuests = 2,
}: BookingModalProps) {
  const router = useRouter();
  const [currentStep, setCurrentStep] = useState<BookingStep>("dates");

  // Form State
  const [startDate, setStartDate] = useState(initialStartDate);
  const [endDate, setEndDate] = useState(initialEndDate);
  const [guestNumber, setGuestNumber] = useState(initialGuests);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [specialRequests, setSpecialRequests] = useState("");

  const [paymentMethod, setPaymentMethod] = useState<"gcash" | "bpi">("gcash");
  const [paymentOption, setPaymentOption] = useState<"half" | "full">("half");
  const [proofFile, setProofFile] = useState<File | null>(null);

  // Authoritative Pricing & Loading States from Laravel /quote endpoint
  const pricePerNight = parseFloat(staycation.price_per_night || String(staycation.house_price || 0)) || 0;
  const [isCalculating, setIsCalculating] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [calcNights, setCalcNights] = useState(1);
  const [calcBasePrice, setCalcBasePrice] = useState(pricePerNight);
  const [calcExtraGuests, setCalcExtraGuests] = useState(0);
  const [calcExtraFee, setCalcExtraFee] = useState(0);
  const [calcTotalPrice, setCalcTotalPrice] = useState(pricePerNight);

  // Error States
  const [dateError, setDateError] = useState("");
  const [guestErrors, setGuestErrors] = useState<Record<string, string>>({});
  const [paymentError, setPaymentError] = useState("");

  // Pre-fill user info if logged in
  useEffect(() => {
    const user = authService.getCurrentUser();
    if (user) {
      if (user.name && !name) setName(user.name);
      if (user.email && !email) setEmail(user.email);
    }
  }, [isOpen]);

  // Sync initial dates
  useEffect(() => {
    if (initialStartDate) setStartDate(initialStartDate);
    if (initialEndDate) setEndDate(initialEndDate);
    if (initialGuests) setGuestNumber(initialGuests);
  }, [initialStartDate, initialEndDate, initialGuests]);

  // Request authoritative quote from Laravel backend whenever dates/guests change
  useEffect(() => {
    if (!startDate || !endDate) return;

    if (new Date(endDate) <= new Date(startDate)) {
      setDateError("Check-out date must be after check-in date.");
      return;
    }

    setDateError("");
    setIsCalculating(true);

    const timer = setTimeout(async () => {
      try {
        const quoteRes = await bookingService.getQuote(staycation.id, {
          start_date: startDate,
          end_date: endDate,
          guest_number: guestNumber,
        });

        if (!quoteRes.is_available) {
          if (quoteRes.conflicts && quoteRes.conflicts.length > 0) {
            const c = quoteRes.conflicts[0];
            setDateError(`Dates conflict with an existing reservation (${c.start_date} to ${c.end_date}).`);
          } else {
            setDateError("The selected dates are currently unavailable.");
          }
        }

        if (quoteRes.quote) {
          const q = quoteRes.quote;
          setCalcNights(q.nights);
          setCalcBasePrice(parseFloat(q.accommodation_total) || 0);
          setCalcExtraGuests(q.extra_guests);
          setCalcExtraFee(parseFloat(q.extra_guest_fee) || 0);
          setCalcTotalPrice(parseFloat(q.total_price) || 0);
        }
      } catch (err: any) {
        if (err instanceof ApiError) {
          if (err.status === 409 || err.errorCode === "dates_unavailable") {
            setDateError("The selected dates are not available for this staycation.");
          } else if (err.status === 422) {
            setDateError(err.getFirstValidationError() || err.message);
          } else {
            setDateError(err.message);
          }
        }
      } finally {
        setIsCalculating(false);
      }
    }, 250);

    return () => clearTimeout(timer);
  }, [staycation.id, startDate, endDate, guestNumber]);

  // Step 1 Validation -> Proceed to Step 2
  const handleProceedToGuests = () => {
    if (!startDate || !endDate) {
      setDateError("Please select both check-in and check-out dates.");
      return;
    }
    if (new Date(endDate) <= new Date(startDate)) {
      setDateError("Check-out date must be after check-in date.");
      return;
    }
    if (dateError) return;

    setCurrentStep("guests");
  };

  // Step 2 Validation -> Proceed to Step 3
  const handleProceedToPayment = () => {
    const errs: Record<string, string> = {};
    if (!name.trim()) errs.name = "Full name is required";
    if (!email.trim() || !email.includes("@")) errs.email = "Valid email is required";
    if (!phone.trim() || phone.length < 10) errs.phone = "Valid contact phone number is required (e.g. 09171234567)";

    setGuestErrors(errs);
    if (Object.keys(errs).length > 0) return;

    setCurrentStep("payment");
  };

  // Final Step: Submit Booking
  const handleFinalSubmit = async () => {
    if (!proofFile) {
      setPaymentError("Please upload a screenshot or photo of your payment receipt.");
      return;
    }

    setPaymentError("");
    setIsSubmitting(true);

    try {
      const formData = new FormData();
      formData.append("staycation_id", String(staycation.id));
      formData.append("start_date", startDate);
      formData.append("end_date", endDate);
      formData.append("guest_number", String(guestNumber));
      formData.append("phone", phone);
      formData.append("payment_type", paymentOption);
      formData.append("payment_method", paymentMethod);
      formData.append("payment_proof", proofFile);
      if (specialRequests.trim()) {
        formData.append("message_to_admin", specialRequests.trim());
      }

      const booking = await bookingService.submit(formData);

      const paidAmount = paymentOption === "half" ? Math.round(calcTotalPrice / 2) : calcTotalPrice;

      // Redirect to Confirmation Page
      const query = new URLSearchParams({
        id: String(booking.id),
        ref: booking.reference || `BK-${String(booking.id).padStart(6, "0")}`,
        villa: staycation.name || staycation.house_name || "Staycation Villa",
        start: startDate,
        end: endDate,
        guests: String(guestNumber),
        paid: String(paidAmount),
        total: String(calcTotalPrice),
        name: name,
        email: email,
        phone: phone,
      }).toString();

      onClose();
      router.push(`/booking/confirmation?${query}`);
    } catch (err: any) {
      if (err instanceof ApiError) {
        if (err.status === 401) {
          setPaymentError("You must be logged in to submit a booking. Please sign in and try again.");
        } else if (err.status === 409 || err.errorCode === "dates_unavailable") {
          setPaymentError("The selected dates have just been reserved by someone else. Please choose different dates.");
        } else if (err.status === 422) {
          if (err.errors?.payment_proof) {
            setPaymentError(err.errors.payment_proof[0]);
          } else if (err.errors?.phone) {
            setPaymentError(err.errors.phone[0]);
          } else {
            setPaymentError(err.getFirstValidationError() || err.message);
          }
        } else {
          setPaymentError(err.message || "Failed to submit booking reservation.");
        }
      } else {
        setPaymentError("Failed to submit reservation. Please check your internet connection and try again.");
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const amountToPay = paymentOption === "half" ? Math.round(calcTotalPrice / 2) : calcTotalPrice;

  return (
    <Dialog
      isOpen={isOpen}
      onClose={onClose}
      title={`Book ${staycation.name || staycation.house_name || "Villa"}`}
      description="Direct staycation reservation with instant booking guarantee."
      maxWidth="lg"
    >
      <div className="space-y-6">
        {/* Multi-step Breadcrumbs Tracker */}
        <div className="flex items-center justify-between border-b border-border/80 pb-4">
          <div className="flex items-center gap-2">
            <span
              className={`h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${
                currentStep === "dates"
                  ? "bg-primary text-primary-foreground"
                  : "bg-muted text-muted-foreground"
              }`}
            >
              1
            </span>
            <span className={`text-xs font-semibold ${currentStep === "dates" ? "text-foreground" : "text-muted-foreground"}`}>
              Dates & Pricing
            </span>
          </div>

          <div className="h-0.5 w-6 bg-border" />

          <div className="flex items-center gap-2">
            <span
              className={`h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${
                currentStep === "guests"
                  ? "bg-primary text-primary-foreground"
                  : "bg-muted text-muted-foreground"
              }`}
            >
              2
            </span>
            <span className={`text-xs font-semibold ${currentStep === "guests" ? "text-foreground" : "text-muted-foreground"}`}>
              Guest Details
            </span>
          </div>

          <div className="h-0.5 w-6 bg-border" />

          <div className="flex items-center gap-2">
            <span
              className={`h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors ${
                currentStep === "payment"
                  ? "bg-primary text-primary-foreground"
                  : "bg-muted text-muted-foreground"
              }`}
            >
              3
            </span>
            <span className={`text-xs font-semibold ${currentStep === "payment" ? "text-foreground" : "text-muted-foreground"}`}>
              Payment & Proof
            </span>
          </div>
        </div>

        {/* STEP 1: DATES & PRICING */}
        {currentStep === "dates" && (
          <div className="space-y-5">
            <DateGuestSelector
              startDate={startDate}
              endDate={endDate}
              guestNumber={guestNumber}
              onStartDateChange={setStartDate}
              onEndDateChange={setEndDate}
              onGuestNumberChange={setGuestNumber}
              error={dateError}
            />

            {startDate && endDate && !dateError && (
              <PricingBreakdown
                nights={calcNights}
                pricePerNight={pricePerNight}
                basePrice={calcBasePrice}
                extraGuests={calcExtraGuests}
                extraFee={calcExtraFee}
                totalPrice={calcTotalPrice}
                paymentOption={paymentOption}
                onPaymentOptionChange={setPaymentOption}
                isLoading={isCalculating}
              />
            )}

            <div className="flex items-center justify-end gap-3 pt-3 border-t border-border">
              <Button variant="outline" onClick={onClose} size="sm">
                Cancel
              </Button>
              <Button
                variant="gold"
                size="sm"
                onClick={handleProceedToGuests}
                disabled={!startDate || !endDate || !!dateError || isCalculating}
                className="gap-2 font-bold"
              >
                Continue to Guest Details
                <ArrowRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}

        {/* STEP 2: GUEST DETAILS */}
        {currentStep === "guests" && (
          <div className="space-y-5">
            <GuestDetailsForm
              name={name}
              email={email}
              phone={phone}
              specialRequests={specialRequests}
              onNameChange={setName}
              onEmailChange={setEmail}
              onPhoneChange={setPhone}
              onSpecialRequestsChange={setSpecialRequests}
              errors={guestErrors}
            />

            <div className="flex items-center justify-between pt-3 border-t border-border">
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setCurrentStep("dates")}
                className="gap-1.5 text-xs text-muted-foreground"
              >
                <ArrowLeft className="h-3.5 w-3.5" /> Back to Dates
              </Button>
              <Button
                variant="gold"
                size="sm"
                onClick={handleProceedToPayment}
                className="gap-2 font-bold"
              >
                Continue to Payment
                <ArrowRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}

        {/* STEP 3: PAYMENT & RECEIPT UPLOAD */}
        {currentStep === "payment" && (
          <div className="space-y-5">
            <PaymentSelection
              paymentMethod={paymentMethod}
              onPaymentMethodChange={setPaymentMethod}
              amountToPay={amountToPay}
              paymentOption={paymentOption}
              proofFile={proofFile}
              onProofFileChange={setProofFile}
              error={paymentError}
            />

            <div className="flex items-center justify-between pt-3 border-t border-border">
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setCurrentStep("guests")}
                className="gap-1.5 text-xs text-muted-foreground"
              >
                <ArrowLeft className="h-3.5 w-3.5" /> Back to Guest Details
              </Button>
              <Button
                variant="default"
                size="sm"
                onClick={handleFinalSubmit}
                isLoading={isSubmitting}
                disabled={isSubmitting || !proofFile}
                className="gap-2 font-bold bg-primary text-primary-foreground shadow-md"
              >
                <Lock className="h-4 w-4 text-gold-300" />
                Submit Reservation & Proof
              </Button>
            </div>
          </div>
        )}
      </div>
    </Dialog>
  );
}
