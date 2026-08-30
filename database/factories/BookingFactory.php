<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->addMonth()->startOfDay();

        return [
            'staycation_id' => Staycation::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '09123456789',
            'status' => BookingStatus::Pending->value,
            'guest_number' => 2,
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays(3)->toDateString(),
            'price_per_day' => 2000,
            'total_price' => 6000,
            'amount_paid' => 0,
            'payment_status' => PaymentStatus::Unpaid->value,
            'payment_method' => 'gcash',
            'payment_proof' => null,
            'transaction_number' => null,
            'message_to_admin' => null,
        ];
    }

    /**
     * Occupy an explicit night range, using the checkout-day-exclusive convention.
     */
    public function forDates(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status->value,
        ]);
    }

    public function halfPaid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Confirmed->value,
            'payment_status' => PaymentStatus::HalfPaid->value,
            'amount_paid' => ((float) ($attributes['total_price'] ?? 0)) / 2,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BookingStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Paid->value,
            'amount_paid' => $attributes['total_price'] ?? 0,
        ]);
    }
}
