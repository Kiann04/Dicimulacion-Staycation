<?php

namespace Tests\Feature;

use App\Exceptions\StaycationUnavailable;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use App\Services\BookingInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsStayDates;
use Tests\Support\RecordingAvailabilityService;
use Tests\TestCase;

/**
 * Serialization of the inventory writes.
 *
 * These tests prove the *shape* of the protection: every inventory write takes
 * the staycation row lock first, inside one transaction, before it reads
 * availability or writes anything, and a row committed by an earlier writer is
 * seen by the next check.
 *
 * They cannot prove that two genuinely simultaneous MySQL connections serialize,
 * because the suite runs on a single in-memory SQLite connection whose grammar
 * compiles `SELECT ... FOR UPDATE` away entirely. Real lock contention has to be
 * verified against MySQL on staging — see the Phase 1 report.
 */
class BookingConcurrencyTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private RecordingAvailabilityService $availability;

    private BookingInventoryService $inventory;

    private Staycation $staycation;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availability = new RecordingAvailabilityService;
        $this->app->instance(BookingAvailabilityService::class, $this->availability);

        $this->inventory = $this->app->make(BookingInventoryService::class);
        $this->staycation = Staycation::factory()->create(['house_price' => 2000]);
        $this->customer = User::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function details(): array
    {
        return [
            'phone' => '09123456789',
            'payment_type' => 'full',
            'payment_method' => 'gcash',
            'payment_proof' => 'payment_proofs/example.jpg',
            'transaction_number' => null,
            'message' => null,
        ];
    }

    private function createBooking(int $startOffset, int $endOffset): Booking
    {
        return $this->inventory->createBooking(
            $this->staycation->getKey(),
            $this->customer,
            2,
            $this->dayAsCarbon($startOffset),
            $this->dayAsCarbon($endOffset),
            $this->details(),
        );
    }

    // ------------------------------------------------------- locking order

    public function test_booking_creation_locks_the_staycation_before_reading_availability(): void
    {
        $this->createBooking(9, 14);

        $this->assertSame(
            ['lock', 'open-for-booking', 'booking-conflict', 'blocked-date-conflict'],
            $this->availability->calls
        );
    }

    public function test_rescheduling_locks_the_staycation_before_reading_availability(): void
    {
        $booking = $this->createBooking(9, 14);
        $this->availability->calls = [];

        $this->inventory->rescheduleBooking(
            $booking,
            $this->staycation->getKey(),
            2,
            $this->dayAsCarbon(40),
            $this->dayAsCarbon(43),
        );

        $this->assertSame(
            ['lock', 'open-for-booking', 'booking-conflict', 'blocked-date-conflict'],
            $this->availability->calls
        );
    }

    public function test_blocking_dates_locks_the_staycation_first(): void
    {
        $this->inventory->createBlockedDate(
            $this->staycation->getKey(),
            $this->dayAsCarbon(60),
            $this->dayAsCarbon(62),
            'Maintenance',
        );

        $this->assertSame('lock', $this->availability->calls[0] ?? null);
    }

    public function test_the_lock_is_taken_before_the_booking_row_is_written(): void
    {
        $lockedBeforeInsert = false;

        Booking::creating(function () use (&$lockedBeforeInsert): void {
            $lockedBeforeInsert = in_array('lock', $this->availability->calls, true);
        });

        try {
            $this->createBooking(9, 14);
        } finally {
            Booking::flushEventListeners();
        }

        $this->assertTrue($lockedBeforeInsert, 'The booking was written before the staycation was locked.');
    }

    // ---------------------------------------------------------- transaction

    public function test_the_whole_inventory_write_happens_in_one_transaction(): void
    {
        // RefreshDatabase already holds a transaction open around each test.
        $baseline = DB::transactionLevel();
        $levelDuringWrite = null;

        Booking::creating(function () use (&$levelDuringWrite): void {
            $levelDuringWrite = DB::transactionLevel();
        });

        try {
            $this->createBooking(9, 14);
        } finally {
            Booking::flushEventListeners();
        }

        $this->assertNotNull($levelDuringWrite);
        $this->assertGreaterThan($baseline, $levelDuringWrite, 'The booking was written outside a transaction.');
        $this->assertSame($baseline, DB::transactionLevel(), 'The transaction was left open.');
    }

    public function test_a_refused_write_rolls_back_and_leaves_nothing_behind(): void
    {
        $baseline = DB::transactionLevel();

        BlockedDate::factory()->for($this->staycation)->create([
            'start_date' => $this->day(10),
            'end_date' => $this->day(11),
        ]);

        try {
            $this->createBooking(9, 14);
            $this->fail('The blocked range should have refused the booking.');
        } catch (StaycationUnavailable) {
            // expected
        }

        $this->assertDatabaseCount('bookings', 0);
        $this->assertSame($baseline, DB::transactionLevel(), 'The transaction was left open.');
    }

    public function test_the_availability_toggle_takes_the_same_lock(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.toggle_availability', $this->staycation))
            ->assertRedirect();

        $this->assertSame('lock', $this->availability->calls[0] ?? null);
        $this->assertSame('unavailable', $this->staycation->refresh()->house_availability);
    }

    public function test_the_availability_toggle_reads_and_writes_under_one_transaction(): void
    {
        $baseline = DB::transactionLevel();
        $levelDuringWrite = null;

        Staycation::updating(function () use (&$levelDuringWrite): void {
            $levelDuringWrite = DB::transactionLevel();
        });

        try {
            $this->actingAs(User::factory()->admin()->create())
                ->post(route('admin.toggle_availability', $this->staycation));
        } finally {
            Staycation::flushEventListeners();
        }

        $this->assertNotNull($levelDuringWrite);
        $this->assertGreaterThan($baseline, $levelDuringWrite);
        $this->assertSame($baseline, DB::transactionLevel());
    }

    /**
     * Rescheduling locks the booking row as well as the staycation, so a payment
     * verification cannot land between the price check and the write.
     */
    public function test_rescheduling_locks_the_booking_row_too(): void
    {
        $booking = $this->createBooking(9, 14);

        $lockedRows = [];

        DB::listen(function ($query) use (&$lockedRows): void {
            if (str_contains(strtolower($query->sql), 'from "bookings"') && str_contains($query->sql, 'limit 1')) {
                $lockedRows[] = $query->sql;
            }
        });

        $this->inventory->rescheduleBooking(
            $booking,
            $this->staycation->getKey(),
            2,
            $this->dayAsCarbon(40),
            $this->dayAsCarbon(43),
        );

        $this->assertNotEmpty($lockedRows, 'Rescheduling never loaded the booking row by key.');
    }

    /**
     * The source staycation id is read from the caller's model before the
     * booking row is locked. If the booking moved properties in between, the
     * lock set is one property out of date — but the target property is still
     * locked, so the availability decision and the write stay correct.
     */
    public function test_a_concurrently_moved_booking_still_reschedules_correctly(): void
    {
        $other = Staycation::factory()->create(['house_price' => 2000]);
        $booking = $this->createBooking(9, 14);

        // The caller's instance still points at the original staycation.
        Booking::whereKey($booking->getKey())->update(['staycation_id' => $other->getKey()]);
        $this->assertSame($this->staycation->getKey(), $booking->staycation_id);

        $this->inventory->rescheduleBooking(
            $booking,
            $this->staycation->getKey(),
            2,
            $this->dayAsCarbon(40),
            $this->dayAsCarbon(43),
        );

        $booking->refresh();

        $this->assertSame($this->staycation->getKey(), $booking->staycation_id);
        $this->assertSame($this->day(40), $booking->start_date->toDateString());
    }

    public function test_a_move_between_properties_locks_both(): void
    {
        $destination = Staycation::factory()->create(['house_price' => 2000]);
        $booking = $this->createBooking(9, 14);
        $this->availability->calls = [];

        $this->inventory->rescheduleBooking(
            $booking,
            $destination->getKey(),
            2,
            $this->dayAsCarbon(40),
            $this->dayAsCarbon(43),
        );

        $lockCount = count(array_filter($this->availability->calls, fn (string $call): bool => $call === 'lock'));

        $this->assertSame(2, $lockCount, 'A move between properties must lock both.');
        $this->assertSame($destination->getKey(), $booking->refresh()->staycation_id);
    }

    // ------------------------------------------------- first writer wins

    public function test_a_booking_committed_first_is_seen_by_the_next_check(): void
    {
        $this->createBooking(9, 14);

        $this->expectException(StaycationUnavailable::class);

        $this->createBooking(11, 13);
    }

    public function test_a_blocked_range_committed_first_wins_over_a_later_booking(): void
    {
        $this->inventory->createBlockedDate(
            $this->staycation->getKey(),
            $this->dayAsCarbon(9),
            $this->dayAsCarbon(14),
            'Maintenance',
        );

        $this->expectException(StaycationUnavailable::class);

        $this->createBooking(9, 14);
    }

    public function test_a_booking_committed_first_wins_over_a_later_block(): void
    {
        $this->createBooking(9, 14);

        $this->expectException(StaycationUnavailable::class);

        $this->inventory->createBlockedDate(
            $this->staycation->getKey(),
            $this->dayAsCarbon(10),
            $this->dayAsCarbon(12),
            'Maintenance',
        );
    }

    public function test_a_property_closed_between_lock_and_write_is_refused(): void
    {
        $this->staycation->update(['house_availability' => 'unavailable']);

        $this->expectException(StaycationUnavailable::class);

        try {
            $this->createBooking(9, 14);
        } finally {
            $this->assertDatabaseCount('bookings', 0);
        }
    }
}
