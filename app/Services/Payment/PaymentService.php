<?php

namespace App\Services\Payment;

use App\Enums\BookingStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\Booking\BookingPricingService;
use App\Services\Payment\Exceptions\InvalidPaymentTransitionException;
use Illuminate\Support\Facades\DB;

/**
 * Owns money movement on a booking.
 *
 * The payments table is the ledger of record. bookings.amount_paid and
 * bookings.payment_status are kept as derived, denormalised mirrors because the
 * existing Blade screens, receipt emails and reports read them directly; every
 * write here updates both sides inside one transaction so they cannot drift.
 */
class PaymentService
{
    public function __construct(
        private BookingPricingService $pricing,
    ) {}

    /**
     * Records money an admin has confirmed arriving. Creates a verified ledger
     * entry and refreshes the booking mirrors.
     */
    public function recordVerifiedPayment(
        Booking $booking,
        string $amount,
        string $type,
        User $verifier,
        ?string $method = null,
        ?string $reference = null,
        ?string $proofPath = null,
        ?string $notes = null,
    ): Payment {
        return DB::transaction(function () use ($booking, $amount, $type, $verifier, $method, $reference, $proofPath, $notes): Payment {
            $payment = Payment::create([
                'booking_id' => $booking->getKey(),
                'amount' => $amount,
                'type' => $type,
                'payment_method' => $method ?? $booking->payment_method,
                'reference_number' => $reference,
                'proof_path' => $proofPath,
                'status' => PaymentRecordStatus::Verified->value,
                'verified_by' => $verifier->getKey(),
                'verified_at' => now(),
                'notes' => $notes,
            ]);

            $this->syncBookingFromLedger($booking);

            return $payment;
        });
    }

    /** Approves a customer-submitted proof and credits the booking. */
    public function verify(Payment $payment, User $verifier): Payment
    {
        return DB::transaction(function () use ($payment, $verifier): Payment {
            $payment->forceFill([
                'status' => PaymentRecordStatus::Verified->value,
                'verified_by' => $verifier->getKey(),
                'verified_at' => now(),
            ])->save();

            $this->syncBookingFromLedger($payment->booking()->firstOrFail());

            return $payment->refresh();
        });
    }

    public function reject(Payment $payment, User $verifier, ?string $notes = null): Payment
    {
        return DB::transaction(function () use ($payment, $verifier, $notes): Payment {
            $payment->forceFill([
                'status' => PaymentRecordStatus::Rejected->value,
                'verified_by' => $verifier->getKey(),
                'verified_at' => now(),
                'notes' => $notes ?? $payment->notes,
            ])->save();

            $this->syncBookingFromLedger($payment->booking()->firstOrFail());

            return $payment->refresh();
        });
    }

    /**
     * Settles the outstanding balance on a half-paid booking.
     *
     * The previous implementation flipped payment_status to "paid" but left
     * amount_paid at the deposit, so every downstream total (receipts, reports,
     * the admin balance column) understated what had been collected. The balance
     * is now written to the ledger and amount_paid is set to the booking total.
     *
     * @throws InvalidPaymentTransitionException
     */
    public function markAsFullyPaid(Booking $booking, User $verifier): Booking
    {
        $current = $booking->paymentStatus();

        if ($current !== PaymentStatus::HalfPaid) {
            throw new InvalidPaymentTransitionException(
                'Only half-paid bookings can be marked as fully paid.'
            );
        }

        return DB::transaction(function () use ($booking, $verifier): Booking {
            $outstanding = $this->outstandingBalance($booking);

            if (bccomp($outstanding, '0.00', 2) === 1) {
                Payment::create([
                    'booking_id' => $booking->getKey(),
                    'amount' => $outstanding,
                    'type' => PaymentType::Balance->value,
                    'payment_method' => $booking->payment_method,
                    'status' => PaymentRecordStatus::Verified->value,
                    'verified_by' => $verifier->getKey(),
                    'verified_at' => now(),
                    'notes' => 'Remaining balance settled by admin.',
                ]);
            }

            $booking->forceFill([
                'amount_paid' => $this->normalise((string) $booking->total_price),
                'payment_status' => PaymentStatus::Paid->value,
                'status' => BookingStatus::Confirmed->value,
            ])->save();

            return $booking->refresh();
        });
    }

    /**
     * Applies an admin-selected payment status, keeping amount_paid and the
     * booking status consistent with it.
     *
     * @throws InvalidPaymentTransitionException
     */
    public function applyPaymentStatus(Booking $booking, string $paymentStatus, User $actor): Booking
    {
        $status = PaymentStatus::tryFrom(strtolower($paymentStatus));

        if ($status === null || ! in_array($status->value, PaymentStatus::adminAssignableValues(), true)) {
            throw new InvalidPaymentTransitionException("'{$paymentStatus}' is not a payment status an admin can set.");
        }

        return DB::transaction(function () use ($booking, $status, $actor): Booking {
            $total = $this->normalise((string) $booking->total_price);

            $amountPaid = match ($status) {
                PaymentStatus::Paid => $total,
                PaymentStatus::HalfPaid => $this->pricing->deposit($total),
                default => '0.00',
            };

            // Mirror the admin's decision into the ledger so the audit trail and
            // amount_paid agree. Existing rows are superseded, not deleted.
            $this->reconcileLedger($booking, $amountPaid, $actor);

            $bookingStatus = match ($status) {
                PaymentStatus::Paid, PaymentStatus::HalfPaid => BookingStatus::Confirmed,
                // Preserves the existing admin workflow, where marking a booking
                // unpaid is how staff void it.
                PaymentStatus::Unpaid => BookingStatus::Cancelled,
                PaymentStatus::Failed => BookingStatus::Declined,
                default => BookingStatus::Approved,
            };

            $booking->forceFill([
                'payment_status' => $status->value,
                'amount_paid' => $amountPaid,
                'status' => $bookingStatus->value,
            ])->save();

            return $booking->refresh();
        });
    }

    public function outstandingBalance(Booking $booking): string
    {
        $total = $this->normalise((string) $booking->total_price);
        $paid = $this->normalise((string) ($booking->amount_paid ?? '0'));

        $balance = bcsub($total, $paid, 2);

        return bccomp($balance, '0.00', 2) === -1 ? '0.00' : $balance;
    }

    /**
     * Recomputes bookings.amount_paid and payment_status from verified ledger
     * rows. Used after a verify/reject so the mirrors follow the ledger.
     */
    public function syncBookingFromLedger(Booking $booking): Booking
    {
        $verifiedTotal = $this->normalise((string) $booking->payments()->verified()->sum('amount'));
        $total = $this->normalise((string) $booking->total_price);

        $paymentStatus = match (true) {
            bccomp($verifiedTotal, '0.00', 2) === 0 => PaymentStatus::Pending,
            bccomp($verifiedTotal, $total, 2) >= 0 => PaymentStatus::Paid,
            default => PaymentStatus::HalfPaid,
        };

        $booking->forceFill([
            'amount_paid' => $verifiedTotal,
            'payment_status' => $paymentStatus->value,
        ]);

        if ($paymentStatus !== PaymentStatus::Pending) {
            $booking->status = BookingStatus::Confirmed->value;
        }

        $booking->save();

        return $booking->refresh();
    }

    /**
     * Brings the ledger in line with an amount an admin set directly. Any
     * previously verified rows are marked rejected rather than deleted, so the
     * history of what was claimed and when stays intact.
     */
    private function reconcileLedger(Booking $booking, string $amountPaid, User $actor): void
    {
        $verifiedTotal = $this->normalise((string) $booking->payments()->verified()->sum('amount'));

        if (bccomp($verifiedTotal, $amountPaid, 2) === 0) {
            return;
        }

        $booking->payments()->verified()->update([
            'status' => PaymentRecordStatus::Rejected->value,
            'notes' => 'Superseded by an admin payment-status correction.',
            'verified_by' => $actor->getKey(),
            'verified_at' => now(),
        ]);

        if (bccomp($amountPaid, '0.00', 2) === 1) {
            Payment::create([
                'booking_id' => $booking->getKey(),
                'amount' => $amountPaid,
                'type' => bccomp($amountPaid, $this->normalise((string) $booking->total_price), 2) >= 0
                    ? PaymentType::Full->value
                    : PaymentType::Deposit->value,
                'payment_method' => $booking->payment_method,
                'reference_number' => $booking->transaction_number,
                'proof_path' => $booking->payment_proof,
                'status' => PaymentRecordStatus::Verified->value,
                'verified_by' => $actor->getKey(),
                'verified_at' => now(),
                'notes' => 'Recorded from an admin payment-status update.',
            ]);
        }
    }

    private function normalise(string $value): string
    {
        return bcadd($value === '' ? '0' : $value, '0', 2);
    }
}
