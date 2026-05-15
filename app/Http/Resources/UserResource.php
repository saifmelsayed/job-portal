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
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value ?? $this->role,
            'status' => $this->status,
            'is_super_admin' => $this->when(
                $this->role === UserRole::Admin,
                (bool) ($this->admin?->is_super_admin),
            ),
            'gender' => $this->when(
                $this->role === UserRole::JobSeeker,
                $this->jobSeekerProfile?->gender
            ),
            'city' => $this->city,
            'street' => $this->street,
            'profile_photo_path' => $this->profile_photo_path,
            'profile_photo_url' => $this->resource->profilePhotoPublicUrl(),
            'email_verified_at' => $this->formatDateTime($this->email_verified_at),
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
            'full_name' => $this->when(
                $this->role === UserRole::JobSeeker,
                $this->jobSeekerProfile?->full_name
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
            'cv_path' => $this->when(
                $this->role === UserRole::JobSeeker,
                $this->jobSeekerProfile?->cv_path
            ),
            'cv_url' => $this->when(
                $this->role === UserRole::JobSeeker && filled($this->jobSeekerProfile?->cv_path),
                fn (): ?string => $this->jobSeekerProfile?->cvPublicUrl(),
            ),
            'disability_type' => $this->when(
                $this->role === UserRole::JobSeeker,
                $this->jobSeekerProfile?->disability_type
            ),
            'subscription' => $this->when(
                $this->role === UserRole::JobSeeker && $this->relationLoaded('activeSeekerSubscription'),
                fn (): ?array => $this->activeSeekerSubscription === null
                    ? null
                    : (new SubscriptionResource($this->activeSeekerSubscription))->resolve($request),
            ),
            'company_name' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->company_name
            ),
            'industry' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->industry
            ),
            'company_size' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->company_size
            ),
            'disability_support_policy' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->disability_support_policy
            ),
            'overview' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->overview
            ),
            'facebook_url' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->facebook_url
            ),
            'x_url' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->x_url
            ),
            'linkedin_url' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->linkedin_url
            ),
            'instagram_url' => $this->when(
                $this->role === UserRole::Company,
                $this->companyProfile?->instagram_url
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
            ->format('M j, Y \a\t g:i A');
    }

    private function formatDate(?CarbonInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->copy()->timezone((string) config('app.timezone', 'Africa/Cairo'))->format('Y-m-d');
    }
}
