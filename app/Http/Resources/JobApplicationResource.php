<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\JobApplication
 */
class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_posting_id' => $this->job_posting_id,
            'status' => $this->status->value,
            'submitted_at' => $this->formatDateTime($this->created_at),
            'job' => (new ApplicationJobSnapshotResource($this->resource))->toArray($request),
            'applicant' => (new ApplicantSubmissionResource($this->resource))->toArray($request),
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
