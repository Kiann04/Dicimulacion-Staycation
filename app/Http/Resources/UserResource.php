<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->usertype,
            'email_verified' => $this->email_verified_at !== null,
            'profile_photo_url' => $this->profile_photo_url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
