<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'profile_photo_url' => $this->profilePhotoPublicUrl(),
            'email_verified_at' => $this->formatDateTime($this->email_verified_at),
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
            'full_name' => $this->when(
                $this->role === UserRole::JobSeeker,
                $this->full_name
            ),
            'skills' => $this->when(
                $this->role === UserRole::JobSeeker,
                fn (): array => $this->skills
                    ->map(fn ($skill): array => [
                        'id' => $skill->id,
                        'name' => $skill->name,
                        'sort_order' => $skill->sort_order,
                    ])
                    ->values()
                    ->all()
            ),
            'educations' => $this->when(
                $this->role === UserRole::JobSeeker,
                fn (): array => $this->educations
                    ->map(fn ($row): array => [
                        'id' => $row->id,
                        'institution' => $row->institution,
                        'degree' => $row->degree,
                        'field_of_study' => $row->field_of_study,
                        'starts_at' => $this->formatDate($row->starts_at),
                        'ends_at' => $this->formatDate($row->ends_at),
                        'details' => $row->details,
                    ])
                    ->values()
                    ->all()
            ),
            'experiences' => $this->when(
                $this->role === UserRole::JobSeeker,
                fn (): array => $this->experiences
                    ->map(fn ($row): array => [
                        'id' => $row->id,
                        'company_name' => $row->company_name,
                        'title' => $row->title,
                        'starts_at' => $this->formatDate($row->starts_at),
                        'ends_at' => $this->formatDate($row->ends_at),
                        'description' => $row->description,
                    ])
                    ->values()
                    ->all()
            ),
            'certificates' => $this->when(
                $this->role === UserRole::JobSeeker,
                fn (): array => $this->certificates
                    ->map(fn ($row): array => [
                        'id' => $row->id,
                        'name' => $row->name,
                        'issuer' => $row->issuer,
                        'issued_at' => $this->formatDate($row->issued_at),
                        'credential_url' => $row->credential_url,
                    ])
                    ->values()
                    ->all()
            ),
            // 'cv_path' => $this->when(
            //     $this->role === UserRole::JobSeeker,
            //     $this->cv_path
            // ),
            'cv_url' => $this->when(
                $this->role === UserRole::JobSeeker && filled($this->cv_path),
                fn (): ?string => $this->cvPublicUrl(),
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

    private function profilePhotoPublicUrl(): ?string
    {
        $path = $this->profile_photo_path;
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Same pattern as profile_photo_url: CV is stored on the public disk so the frontend can use this directly (e.g. iframe/embed).
     */
    private function cvPublicUrl(): ?string
    {
        $path = $this->cv_path;
        if ($path === null || $path === '' || str_contains($path, '..')) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
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

    private function formatDate(?CarbonInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->copy()->timezone((string) config('app.timezone', 'Africa/Cairo'))->format('Y-m-d');
    }
}
