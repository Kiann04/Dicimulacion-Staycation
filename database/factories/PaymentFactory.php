<?php

namespace Database\Factories;

use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentType;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => 1500,
            'type' => PaymentType::Deposit->value,
            'payment_method' => 'gcash',
            'reference_number' => fake()->bothify('REF-#####'),
            'proof_path' => null,
            'status' => PaymentRecordStatus::Pending->value,
            'verified_by' => null,
            'verified_at' => null,
            'notes' => null,
        ];
    }

    public function verified(?User $verifier = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRecordStatus::Verified->value,
            'verified_by' => $verifier?->getKey() ?? User::factory()->admin(),
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentRecordStatus::Rejected->value,
            'verified_at' => now(),
        ]);
    }
}
