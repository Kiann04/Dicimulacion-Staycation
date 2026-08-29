import React from "react";

export default function TermsPage() {
  return (
    <div className="min-h-screen py-16 bg-background">
      <div className="container mx-auto px-6 max-w-3xl">
        <h1 className="font-serif text-3xl sm:text-4xl font-bold text-foreground mb-4">
          Terms & Booking Conditions
        </h1>
        <p className="text-xs text-muted-foreground mb-8">Effective Date: January 1, 2026</p>

        <div className="prose dark:prose-invert space-y-6 text-sm text-foreground/90 leading-relaxed">
          <section className="space-y-2">
            <h2 className="font-serif text-lg font-bold text-foreground">1. Reservation & Downpayment</h2>
            <p>
              To confirm any staycation reservation with Dicimulacion Staycation, a minimum of 50% downpayment is required via GCash or BPI bank deposit. The booking will remain in pending status until the payment proof has been reviewed and verified by our management team.
            </p>
          </section>

          <section className="space-y-2">
            <h2 className="font-serif text-lg font-bold text-foreground">2. Check-In & Check-Out Policy</h2>
            <p>
              Standard check-in time is at 2:00 PM (PST) and standard check-out time is at 12:00 PM (Noon PST) the following day. Early check-in or late check-out is subject to property availability and an hourly fee of ₱500/hour.
            </p>
          </section>

          <section className="space-y-2">
            <h2 className="font-serif text-lg font-bold text-foreground">3. Guest Capacity & Extra Persons</h2>
            <p>
              Standard rate includes up to 6 guests. Additional guests beyond 6 are charged ₱500 per person per night, with additional sleeping mattresses and beddings provided upon request.
            </p>
          </section>

          <section className="space-y-2">
            <h2 className="font-serif text-lg font-bold text-foreground">4. Cancellation & Rescheduling</h2>
            <p>
              Cancellations made at least 7 days prior to check-in are eligible for a 100% full refund or free rescheduling. Cancellations made within 3–6 days may reschedule once without penalty. Cancellations within 48 hours are non-refundable.
            </p>
          </section>
        </div>
      </div>
    </div>
  );
}
