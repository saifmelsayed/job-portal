<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Job fields stored on the application at submit time.
 *
 * @mixin \App\Models\JobApplication
 */
class ApplicationJobSnapshotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->job_title,
            'company_name' => $this->resolveCompanyName(),
            'company_profile_photo_url' => $this->resolveCompanyProfilePhotoUrl(),
            'company_industry' => $this->resolveCompanyIndustry(),
            'company_size' => $this->resolveCompanySize(),
            'description' => $this->job_description,
            'requirements' => $this->job_requirements,
            'qualification' => $this->job_qualification,
            'location' => $this->job_location,
            'type' => $this->job_type,
            'approved_disability' => array_values($this->job_approved_disability ?? []),
        ];
    }

    private function resolveCompanyName(): ?string
    {
        return $this->resolveCompanyOwner()?->companyProfile?->company_name;
    }

    private function resolveCompanyProfilePhotoUrl(): ?string
    {
        return $this->resolveCompanyOwner()?->profilePhotoPublicUrl();
    }

    private function resolveCompanyIndustry(): ?string
    {
        return $this->resolveCompanyOwner()?->companyProfile?->industry;
    }

    private function resolveCompanySize(): ?string
    {
        return $this->resolveCompanyOwner()?->companyProfile?->company_size;
    }

    private function resolveCompanyOwner(): ?User
    {
        if (! $this->relationLoaded('jobPosting')) {
            return null;
        }

        $posting = $this->jobPosting;
        if ($posting === null) {
            return null;
        }

        if ($posting->relationLoaded('user')) {
            return $posting->user;
        }

        return User::query()
            ->whereKey($posting->user_id)
            ->with('companyProfile')
            ->first();
    }
}
