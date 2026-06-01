<?php

namespace App\Http\Resources;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Job fields resolved from the related posting.
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
        $posting = $this->resolveJobPosting();

        return [
            'title' => $posting?->title,
            'company_name' => $this->resolveCompanyName($posting),
            'company_profile_photo_url' => $this->resolveCompanyProfilePhotoUrl($posting),
            'company_industry' => $this->resolveCompanyIndustry($posting),
            'company_size' => $this->resolveCompanySize($posting),
            'description' => $posting?->description,
            'requirements' => $posting?->requirements,
            'qualification' => $posting?->qualification,
            'location' => $posting?->location,
            'type' => $posting?->type?->value ?? $posting?->type,
            'approved_disability' => array_values($posting?->approved_disability ?? []),
            'category' => $posting?->category,
            'skills' => array_values($posting?->skills ?? []),
        ];
    }

    private function resolveJobPosting(): ?JobPosting
    {
        if ($this->relationLoaded('jobPosting')) {
            return $this->jobPosting;
        }

        return $this->jobPosting()->first();
    }

    private function resolveCompanyName(?JobPosting $posting): ?string
    {
        return $this->resolveCompanyOwner($posting)?->companyProfile?->company_name;
    }

    private function resolveCompanyProfilePhotoUrl(?JobPosting $posting): ?string
    {
        return $this->resolveCompanyOwner($posting)?->profilePhotoPublicUrl();
    }

    private function resolveCompanyIndustry(?JobPosting $posting): ?string
    {
        return $this->resolveCompanyOwner($posting)?->companyProfile?->industry;
    }

    private function resolveCompanySize(?JobPosting $posting): ?string
    {
        return $this->resolveCompanyOwner($posting)?->companyProfile?->company_size;
    }

    private function resolveCompanyOwner(?JobPosting $posting): ?User
    {
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
