<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StaffAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createStaff(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.createStaff'), array_merge([
            'name' => 'New Staffer',
            'email' => 'staffer@example.com',
            'password' => 'Str0ngPassphrase',
        ], $overrides));
    }

    // ------------------------------------------------------ creation policy

    public function test_an_administrator_can_create_a_staff_account(): void
    {
        $this->createStaff()->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'staffer@example.com',
            'usertype' => 'staff',
        ]);
    }

    public function test_a_short_password_is_rejected(): void
    {
        $this->createStaff(['password' => 'Ab1cdef'])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'staffer@example.com']);
    }

    public function test_a_six_character_password_is_no_longer_accepted(): void
    {
        $this->createStaff(['password' => 'Abc123'])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'staffer@example.com']);
    }

    public function test_a_password_without_mixed_case_is_rejected(): void
    {
        $this->createStaff(['password' => 'alllowercase1'])->assertSessionHasErrors('password');
    }

    public function test_a_password_without_a_number_is_rejected(): void
    {
        $this->createStaff(['password' => 'NoDigitsAtAll'])->assertSessionHasErrors('password');
    }

    public function test_a_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'staffer@example.com']);

        $this->createStaff()->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'staffer@example.com')->count());
    }

    public function test_the_stored_password_is_hashed(): void
    {
        $this->createStaff();

        $staff = User::query()->where('email', 'staffer@example.com')->sole();

        $this->assertNotSame('Str0ngPassphrase', $staff->password);
        $this->assertTrue(Hash::check('Str0ngPassphrase', $staff->password));
    }

    // ------------------------------------------------------ deletion scope

    public function test_an_administrator_can_delete_a_staff_account(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.deleteStaff', $staff))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $staff->getKey()]);
    }

    public function test_the_staff_endpoint_cannot_delete_a_customer(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.deleteStaff', $customer))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $customer->getKey()]);
    }

    public function test_the_staff_endpoint_cannot_delete_an_administrator(): void
    {
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.deleteStaff', $otherAdmin))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $otherAdmin->getKey()]);
    }

    public function test_an_administrator_cannot_delete_their_own_account_here(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.deleteStaff', $this->admin))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $this->admin->getKey()]);
    }

    // ------------------------------------------- authenticated staff access

    public function test_staff_can_reach_their_own_dashboard(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('staff.dashboard'))
            ->assertOk();
    }

    public function test_staff_can_list_customers(): void
    {
        User::factory()->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('staff.customers'))
            ->assertOk();
    }

    public function test_staff_can_list_bookings(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('staff.bookings'))
            ->assertOk();
    }

    public function test_staff_can_read_messages(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('staff.messages'))
            ->assertOk();
    }

    public function test_staff_can_open_a_single_message(): void
    {
        $inquiry = Inquiry::query()->create([
            'email' => 'guest@example.com',
            'message' => 'Is the pool heated?',
            'status' => 'unread',
        ]);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('staff.view_message', $inquiry))
            ->assertOk();

        $this->assertSame('read', $inquiry->refresh()->status);
    }
}
