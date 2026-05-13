<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row in the company applicants dashboard (read-only listing).
 *
 * @mixin \App\Models\JobApplication
 */
class CompanyJobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'submitted_at' => $this->formatDateTime($this->created_at),
            'job_title' => $this->job_title,
            'name' => $this->applicant_name,
            'email' => $this->applicant_email,
            'phone' => $this->applicant_phone,
            'linkedin' => $this->applicant_linkedin,
            //'cv' => basename($this->cv_path),
            'cv_url' => $this->resource->cvPublicUrl(),
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
