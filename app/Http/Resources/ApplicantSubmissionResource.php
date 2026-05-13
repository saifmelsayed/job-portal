<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Applicant data as submitted on a job application.
 *
 * @mixin \App\Models\JobApplication
 */
class ApplicantSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->applicant_name,
            'email' => $this->applicant_email,
            'phone' => $this->applicant_phone,
            'linkedin' => $this->applicant_linkedin,
            //'cv_filename' => basename($this->cv_path),
            'cv_url' => $this->resource->cvPublicUrl(),
        ];
    }
}
