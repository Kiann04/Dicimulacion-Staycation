<?php

namespace Tests\Feature\Payment;

use App\Enums\BookingStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staycation;
use App\Models\User;
use App\Services\Payment\Exceptions\InvalidPaymentTransitionException;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $payments;

    private User $admin;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->payments = app(PaymentService::class);
        $this->admin = User::factory()->admin()->create();

        $this->booking = Booking::factory()
            ->for(Staycation::factory()->pricedAt(3000))
            ->forDates('2026-10-10', '2026-10-13')
            ->create([
                'total_price' => '9000.00',
                'amount_paid' => '0.00',
                'payment_status' => PaymentStatus::Pending->value,
                'status' => BookingStatus::Approved->value,
            ]);
    }

    /** Verified ledger total, normalised to two decimals across database drivers. */
    private function verifiedTotal(): string
    {
        return bcadd(
            (string) Payment::where('booking_id', $this->booking->getKey())->verified()->sum('amount'),
            '0',
            2,
        );
    }

    public function test_marking_a_booking_half_paid_credits_the_deposit(): void
    {
        $booking = $this->payments->applyPaymentStatus(
            $this->booking,
            PaymentStatus::HalfPaid->value,
            $this->admin,
        );

        $this->assertSame(PaymentStatus::HalfPaid->value, $booking->payment_status);
        $this->assertSame('4500.00', (string) $booking->amount_paid);
        $this->assertSame('4500.00', $booking->balanceDue());
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);
    }

    public function test_marking_a_booking_paid_credits_the_full_total(): void
    {
        $booking = $this->payments->applyPaymentStatus(
            $this->booking,
            PaymentStatus::Paid->value,
            $this->admin,
        );

        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
        $this->assertSame('9000.00', (string) $booking->amount_paid);
        $this->assertSame('0.00', $booking->balanceDue());
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);
    }

    /**
     * The regression this phase set out to fix: mark-as-fully-paid used to flip
     * payment_status to "paid" and leave amount_paid at the deposit, so every
     * receipt and report understated what had been collected.
     */
    public function test_mark_as_fully_paid_sets_amount_paid_to_the_booking_total(): void
    {
        $this->payments->applyPaymentStatus($this->booking, PaymentStatus::HalfPaid->value, $this->admin);

        $this->assertSame('4500.00', (string) $this->booking->refresh()->amount_paid);

        $booking = $this->payments->markAsFullyPaid($this->booking->refresh(), $this->admin);

        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
        $this->assertSame('9000.00', (string) $booking->amount_paid);
        $this->assertSame('0.00', $booking->balanceDue());
    }

    public function test_mark_as_fully_paid_writes_the_balance_to_the_ledger(): void
    {
        $this->payments->applyPaymentStatus($this->booking, PaymentStatus::HalfPaid->value, $this->admin);
        $this->payments->markAsFullyPaid($this->booking->refresh(), $this->admin);

        $balance = Payment::where('booking_id', $this->booking->getKey())
            ->where('type', PaymentType::Balance->value)
            ->first();

        $this->assertNotNull($balance);
        $this->assertSame('4500.00', (string) $balance->amount);
        $this->assertSame(PaymentRecordStatus::Verified->value, $balance->status);
        $this->assertSame($this->admin->getKey(), $balance->verified_by);

        // Deposit + balance reconstruct the total exactly.
        $this->assertSame('9000.00', $this->verifiedTotal());
    }

    public function test_mark_as_fully_paid_is_rejected_for_a_booking_that_is_not_half_paid(): void
    {
        $this->expectException(InvalidPaymentTransitionException::class);

        $this->payments->markAsFullyPaid($this->booking, $this->admin);
    }

    public function test_an_unrecognised_payment_status_is_rejected(): void
    {
        $this->expectException(InvalidPaymentTransitionException::class);

        $this->payments->applyPaymentStatus($this->booking, 'totally-paid', $this->admin);
    }

    public function test_verifying_a_ledger_payment_credits_the_booking(): void
    {
        $payment = Payment::factory()->create([
            'booking_id' => $this->booking->getKey(),
            'amount' => '9000.00',
            'type' => PaymentType::Full->value,
            'status' => PaymentRecordStatus::Pending->value,
        ]);

        $this->payments->verify($payment, $this->admin);

        $booking = $this->booking->refresh();

        $this->assertSame('9000.00', (string) $booking->amount_paid);
        $this->assertSame(PaymentStatus::Paid->value, $booking->payment_status);
        $this->assertSame(BookingStatus::Confirmed->value, $booking->status);
    }

    public function test_verifying_a_partial_payment_leaves_the_booking_half_paid(): void
    {
        $payment = Payment::factory()->create([
            'booking_id' => $this->booking->getKey(),
            'amount' => '4500.00',
            'status' => PaymentRecordStatus::Pending->value,
        ]);

        $this->payments->verify($payment, $this->admin);

        $booking = $this->booking->refresh();

        $this->assertSame('4500.00', (string) $booking->amount_paid);
        $this->assertSame(PaymentStatus::HalfPaid->value, $booking->payment_status);
    }

    public function test_rejecting_a_payment_does_not_credit_the_booking(): void
    {
        $payment = Payment::factory()->create([
            'booking_id' => $this->booking->getKey(),
            'amount' => '9000.00',
            'status' => PaymentRecordStatus::Pending->value,
        ]);

        $this->payments->reject($payment, $this->admin, 'Screenshot was unreadable.');

        $this->assertSame(PaymentRecordStatus::Rejected->value, $payment->refresh()->status);
        $this->assertSame('0.00', (string) $this->booking->refresh()->amount_paid);
    }

    public function test_only_verified_payments_count_toward_the_amount_paid(): void
    {
        Payment::factory()->create([
            'booking_id' => $this->booking->getKey(),
            'amount' => '4500.00',
            'status' => PaymentRecordStatus::Verified->value,
        ]);

        Payment::factory()->create([
            'booking_id' => $this->booking->getKey(),
            'amount' => '4500.00',
            'status' => PaymentRecordStatus::Pending->value,
        ]);

        Payment::factory()->create([
            'booking_id' => $this->booking->getKey(),
            'amount' => '1000.00',
            'status' => PaymentRecordStatus::Rejected->value,
        ]);

        $booking = $this->payments->syncBookingFromLedger($this->booking);

        $this->assertSame('4500.00', (string) $booking->amount_paid);
        $this->assertSame(PaymentStatus::HalfPaid->value, $booking->payment_status);
    }

    /**
     * Marking a booking unpaid is how the existing admin workflow voids it. The
     * behaviour is preserved, but amount_paid is now cleared to match.
     */
    public function test_marking_a_booking_unpaid_voids_it_and_clears_the_amount_paid(): void
    {
        $this->payments->applyPaymentStatus($this->booking, PaymentStatus::Paid->value, $this->admin);

        $booking = $this->payments->applyPaymentStatus(
            $this->booking->refresh(),
            PaymentStatus::Unpaid->value,
            $this->admin,
        );

        $this->assertSame('0.00', (string) $booking->amount_paid);
        $this->assertSame(BookingStatus::Cancelled->value, $booking->status);
    }

    public function test_a_voided_booking_releases_its_dates(): void
    {
        $this->payments->applyPaymentStatus($this->booking, PaymentStatus::Unpaid->value, $this->admin);

        $this->assertFalse($this->booking->refresh()->blocksAvailability());
    }

    public function test_correcting_a_payment_status_supersedes_rather_than_deletes_ledger_rows(): void
    {
        $this->payments->applyPaymentStatus($this->booking, PaymentStatus::Paid->value, $this->admin);
        $this->payments->applyPaymentStatus($this->booking->refresh(), PaymentStatus::HalfPaid->value, $this->admin);

        // The original row is kept for the audit trail, marked rejected.
        $this->assertSame(2, Payment::where('booking_id', $this->booking->getKey())->count());
        $this->assertSame('4500.00', $this->verifiedTotal());
    }

    public function test_recording_a_verified_payment_credits_the_booking_immediately(): void
    {
        $this->payments->recordVerifiedPayment(
            booking: $this->booking,
            amount: '9000.00',
            type: PaymentType::Full->value,
            verifier: $this->admin,
            method: 'bpi',
            reference: 'REF-9',
        );

        $this->assertSame('9000.00', (string) $this->booking->refresh()->amount_paid);
    }
}
