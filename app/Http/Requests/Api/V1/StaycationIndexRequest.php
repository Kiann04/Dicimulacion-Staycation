<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StaycationIndexRequest extends FormRequest
{
    /**
     * Page size used when the caller does not ask for one.
     */
    public const DEFAULT_PER_PAGE = 15;

    /**
     * Largest page a caller may request, so one call cannot ask the database
     * for every row.
     */
    public const MAXIMUM_PER_PAGE = 50;

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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAXIMUM_PER_PAGE],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.max' => 'A page may contain at most :max staycations.',
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', self::DEFAULT_PER_PAGE);
    }
}
