<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Services\PaymentProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

class PaymentProofSecurityTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private Staycation $staycation;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(PaymentProofService::DISK);

        $this->staycation = Staycation::factory()->create(['house_price' => 2000]);
        $this->customer = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submit(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->customer)->post(route('booking.submit', $this->staycation), array_merge([
            'guest_number' => 2,
            'startDate' => $this->day(9),
            'endDate' => $this->day(14),
            'payment_type' => 'half',
            'payment_method' => 'gcash',
            'payment_proof' => UploadedFile::fake()->create('proof.jpg', 64, 'image/jpeg'),
            'phone' => '09123456789',
        ], $overrides));
    }

    public function test_an_uploaded_proof_is_stored_on_the_private_disk(): void
    {
        $this->submit()->assertRedirect();

        $booking = Booking::sole();

        Storage::disk(PaymentProofService::DISK)->assertExists($booking->payment_proof);
        $this->assertStringStartsWith(PaymentProofService::DIRECTORY.'/', $booking->payment_proof);
    }

    public function test_a_stored_proof_is_not_named_after_the_uploaded_file(): void
    {
        $this->submit(['payment_proof' => UploadedFile::fake()->create('my-receipt.jpg', 64, 'image/jpeg')])
            ->assertRedirect();

        $this->assertStringNotContainsString('my-receipt', Booking::sole()->payment_proof);
    }

    public function test_a_proof_that_is_not_an_image_is_rejected(): void
    {
        $this->submit(['payment_proof' => UploadedFile::fake()->create('payload.php', 8, 'text/x-php')])
            ->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_pdf_proof_is_rejected(): void
    {
        $this->submit(['payment_proof' => UploadedFile::fake()->create('receipt.pdf', 64, 'application/pdf')])
            ->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_an_oversized_proof_is_rejected(): void
    {
        $this->submit(['payment_proof' => UploadedFile::fake()->create('huge.jpg', 6144, 'image/jpeg')])
            ->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_missing_proof_is_rejected(): void
    {
        $this->submit(['payment_proof' => null])->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseCount('bookings', 0);
    }

    /**
     * The local disk is configured with `throw => false`, so a failed write
     * returns false instead of raising. Returning a path for a file that was
     * never written would persist a booking claiming a proof it does not have.
     */
    public function test_a_silent_storage_failure_aborts_the_submission(): void
    {
        $disk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturnFalse();
        $disk->shouldReceive('exists')->andReturnFalse();

        Storage::shouldReceive('disk')
            ->with(PaymentProofService::DISK)
            ->andReturn($disk);

        $this->submit()->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_storage_write_that_reports_success_but_leaves_no_file_is_refused(): void
    {
        $disk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn('payment_proofs/ghost.jpg');
        $disk->shouldReceive('exists')->andReturnFalse();

        Storage::shouldReceive('disk')
            ->with(PaymentProofService::DISK)
            ->andReturn($disk);

        $this->submit()->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_guest_cannot_read_a_payment_proof(): void
    {
        $booking = Booking::factory()->for($this->customer)->create([
            'payment_proof' => PaymentProofService::DIRECTORY.'/proof.jpg',
        ]);

        Storage::disk(PaymentProofService::DISK)
            ->put(PaymentProofService::DIRECTORY.'/proof.jpg', 'bytes');

        $this->get(route('bookings.payment_proof', $booking))
            ->assertRedirect(route('login'));
    }

    public function test_a_customer_cannot_read_another_customers_payment_proof(): void
    {
        $this->submit();

        $this->actingAs(User::factory()->create())
            ->get(route('bookings.payment_proof', Booking::sole()))
            ->assertForbidden();
    }

    public function test_ordinary_staff_cannot_read_a_customers_payment_proof(): void
    {
        $this->submit();

        // Staff see bookings, but a payment proof is a customer financial
        // document and is not theirs to open.
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('bookings.payment_proof', Booking::sole()))
            ->assertForbidden();
    }

    public function test_a_customer_can_read_their_own_payment_proof(): void
    {
        $this->submit();

        $this->actingAs($this->customer)
            ->get(route('bookings.payment_proof', Booking::sole()))
            ->assertOk();
    }

    public function test_an_administrator_can_read_any_payment_proof(): void
    {
        $this->submit();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('bookings.payment_proof', Booking::sole()))
            ->assertOk();
    }

    public function test_a_stored_path_cannot_escape_the_proof_directory(): void
    {
        $this->submit();

        $booking = Booking::sole();
        $booking->forceFill(['payment_proof' => '../../../.env'])->save();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('bookings.payment_proof', $booking))
            ->assertNotFound();
    }

    public function test_the_admin_proof_endpoint_links_to_the_authorized_route(): void
    {
        $this->submit();

        $booking = Booking::sole();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.bookings.proof', $booking))
            ->assertOk()
            ->assertJsonPath('proof', route('bookings.payment_proof', $booking));
    }

    public function test_the_legacy_public_proof_directory_denies_direct_access(): void
    {
        $htaccess = public_path('payment_proofs/.htaccess');

        $this->assertFileExists($htaccess);
        $this->assertStringContainsString('Require all denied', file_get_contents($htaccess));
    }
}
