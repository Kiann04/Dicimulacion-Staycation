<?php

namespace App\Http\Requests\Api\V1;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dates for an availability lookup.
 *
 * The rules mirror the customer booking rules of Phase 1: an arrival date may
 * not be in the past, and departure must come after arrival. `date_format`
 * rather than the looser `date` because this is a published contract — a
 * frontend should be told that "next friday" is not a date, not have it
 * silently interpreted.
 *
 * `end_date` is the checkout day and is not itself occupied, so the shortest
 * valid range is a single night.
 */
class StaycationAvailabilityRequest extends FormRequest
{
    public const DATE_FORMAT = 'Y-m-d';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:'.self::DATE_FORMAT, 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:'.self::DATE_FORMAT, 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.date_format' => 'The arrival date must be formatted as YYYY-MM-DD.',
            'start_date.after_or_equal' => 'The arrival date cannot be in the past.',
            'end_date.date_format' => 'The departure date must be formatted as YYYY-MM-DD.',
            'end_date.after' => 'The departure date must be after the arrival date.',
        ];
    }

    public function startDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('start_date'))->startOfDay();
    }

    public function endDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('end_date'))->startOfDay();
    }
}
