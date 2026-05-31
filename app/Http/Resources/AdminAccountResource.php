<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
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
            'full_name' => $this->full_name,
            'status' => $this->status,
            'is_super_admin' => (bool) ($this->admin?->is_super_admin),
            'created_at' => $this->formatDateTime($this->created_at),
        ];
    }

    private function formatDateTime(?CarbonInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value
            ->copy()
            ->timezone((string) config('app.timezone', 'Africa/Cairo'))
            ->format('M j, Y \a\t g:i A');
    }
}
