<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreStaycationRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'house_name' => 'required|string|max:255',
            'house_description' => 'required|string',
            'house_price' => 'required|numeric|min:0|max:9999999.99',
            'house_location' => 'required|string|max:255',
            'house_availability' => ['nullable', Rule::in(['available', 'unavailable'])],
        ];
    }
}
