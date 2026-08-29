<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(5, 40))->startOfDay();
        $end = $start->copy()->addDays(fake()->numberBetween(1, 4));

        return [
            'staycation_id' => Staycation::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('09#########'),
            'status' => BookingStatus::Pending->value,
            'guest_number' => fake()->numberBetween(1, 6),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'price_per_day' => 3000,
            'total_price' => 3000,
            'amount_paid' => 0,
            'payment_status' => PaymentStatus::Pending->value,
            'payment_method' => 'gcash',
            'payment_proof' => null,
            'transaction_number' => fake()->bothify('TXN-#####'),
            'message_to_admin' => null,
        ];
    }

    /** Places the stay on an exact date range, which is what availability tests need. */
    public function forDates(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    public function withStatus(BookingStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status->value,
        ]);
    }

    public function pending(): static
    {
        return $this->withStatus(BookingStatus::Pending);
    }

    public function approved(): static
    {
        return $this->withStatus(BookingStatus::Approved);
    }

    public function confirmed(): static
    {
        return $this->withStatus(BookingStatus::Confirmed);
    }

    public function cancelled(): static
    {
        return $this->withStatus(BookingStatus::Cancelled);
    }

    public function declined(): static
    {
        return $this->withStatus(BookingStatus::Declined);
    }

    public function halfPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed->value,
            'payment_status' => PaymentStatus::HalfPaid->value,
            'amount_paid' => bcdiv((string) ($attributes['total_price'] ?? '0'), '2', 2),
        ]);
    }

    public function fullyPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed->value,
            'payment_status' => PaymentStatus::Paid->value,
            'amount_paid' => $attributes['total_price'] ?? 0,
        ]);
    }
}
