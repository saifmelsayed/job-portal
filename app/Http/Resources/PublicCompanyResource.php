<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class PublicCompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->companyProfile?->company_name,
            'industry' => $this->companyProfile?->industry,
            'company_size' => $this->companyProfile?->company_size,
            'disability_support_policy' => $this->companyProfile?->disability_support_policy,
            'overview' => $this->companyProfile?->overview,
            'facebook_url' => $this->companyProfile?->facebook_url,
            'x_url' => $this->companyProfile?->x_url,
            'linkedin_url' => $this->companyProfile?->linkedin_url,
            'instagram_url' => $this->companyProfile?->instagram_url,
            'profile_photo_url' => $this->profilePhotoPublicUrl(),
            'city' => $this->city,
            'street' => $this->street,
            'job_postings_count' => (int) ($this->job_postings_count ?? 0),
        ];
    }
}
