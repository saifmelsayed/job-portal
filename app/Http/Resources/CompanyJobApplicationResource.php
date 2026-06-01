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
        $applicant = $this->relationLoaded('applicant') ? $this->applicant : null;
        $profile = $applicant?->jobSeekerProfile;
        $posting = $this->relationLoaded('jobPosting') ? $this->jobPosting : null;

        return [
            'id' => $this->id,
            'job_posting_id' => $this->job_posting_id,
            'status' => $this->status->value,
            'submitted_at' => $this->formatDateTime($this->created_at),
            'user_id' => $this->user_id,
            'job_title' => $posting?->title,
            'name' => is_string($applicant?->full_name) ? trim($applicant->full_name) : null,
            'email' => $applicant?->email,
            'phone' => $applicant?->phone,
            'linkedin' => $profile?->linkedin_url,
            'cv_url' => $profile?->cvPublicUrl(),
            'seeker_profile' => $this->when(
                $applicant !== null,
                fn (): array => (new UserResource($applicant))->resolve($request)
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
}
