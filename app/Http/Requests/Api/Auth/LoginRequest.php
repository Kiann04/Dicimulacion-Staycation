<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ];
    }

    /** Names the issued token after the calling device, so tokens stay revocable. */
    public function deviceName(): string
    {
        return $this->string('device_name')->isNotEmpty()
            ? $this->string('device_name')->toString()
            : 'api';
    }
}
