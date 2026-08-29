<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateStaycationRequest extends ApiFormRequest
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
            'house_name' => 'sometimes|required|string|max:255',
            'house_description' => 'sometimes|required|string',
            'house_price' => 'sometimes|required|numeric|min:0|max:9999999.99',
            'house_location' => 'sometimes|required|string|max:255',
            'house_availability' => ['sometimes', 'required', Rule::in(['available', 'unavailable'])],
        ];
    }
}
