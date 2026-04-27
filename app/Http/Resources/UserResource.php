<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value ?? $this->role,
            'status' => $this->status,
            'gender' => $this->gender,
            'city' => $this->city,
            'street' => $this->street,
            'profile_photo_path' => $this->profile_photo_path,
            'email_verified_at' => $this->formatDateTime($this->email_verified_at),
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
            'full_name' => $this->when(
                $this->role === UserRole::JobSeeker,
                $this->full_name
            ),
            'skills' => $this->when(
                $this->role === UserRole::JobSeeker,
                $this->skills
            ),
            'cv_path' => $this->when(
                $this->role === UserRole::JobSeeker,
                $this->cv_path
            ),
            'company_name' => $this->when(
                $this->role === UserRole::Company,
                $this->company_name
            ),
            'industry' => $this->when(
                $this->role === UserRole::Company,
                $this->industry
            ),
            'company_size' => $this->when(
                $this->role === UserRole::Company,
                $this->company_size
            ),
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
            ->format('M j, Y \a\t g:i A T');
    }
}
