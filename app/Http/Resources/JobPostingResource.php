<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\JobPosting
 */
class JobPostingResource extends JsonResource
{
    public function __construct($resource, private bool $includeOwnerId = true)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $body = [
            'title' => $this->title,
            'company_name' => $this->user?->companyProfile?->company_name,
            'company_profile_photo_url' => $this->user?->profilePhotoPublicUrl(),
            'company_industry' => $this->user?->companyProfile?->industry,
            'company_size' => $this->user?->companyProfile?->company_size,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'qualification' => $this->qualification,
            'location' => $this->location,
            'type' => $this->type->value,
            'approved_disability' => array_values($this->approved_disability ?? []),
            'created_at' => $this->formatDateTime($this->created_at),
            'updated_at' => $this->formatDateTime($this->updated_at),
        ];

        if ($this->includeOwnerId) {
            return [
                'id' => $this->id,
                'user_id' => $this->user_id,
                ...$body,
            ];
        }

        return [
            'id' => $this->id,
            ...$body,
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
