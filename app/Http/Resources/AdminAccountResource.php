<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Small JSON shape for admin accounts in the super-admin sub-admin API.
 */
class AdminAccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'status' => $this->status,
            'is_super_admin' => (bool) ($this->admin?->is_super_admin),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
