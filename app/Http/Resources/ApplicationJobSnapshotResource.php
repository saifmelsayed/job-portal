<?php

namespace App\Http\Resources;

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
            'description' => $this->job_description,
            'requirements' => $this->job_requirements,
            'qualification' => $this->job_qualification,
            'location' => $this->job_location,
            'type' => $this->job_type,
        ];
    }

    private function resolveCompanyName(): ?string
    {
        if (! $this->relationLoaded('jobPosting')) {
            return null;
        }

        $posting = $this->jobPosting;
        if ($posting === null) {
            return null;
        }

        if ($posting->relationLoaded('user')) {
            return $posting->user?->company_name;
        }

        return $posting->user()->value('company_name');
    }
}
