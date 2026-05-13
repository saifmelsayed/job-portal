<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'job_posting_id',
    'user_id',
    'status',
    'job_title',
    'job_description',
    'job_requirements',
    'job_qualification',
    'job_location',
    'job_type',
    'applicant_name',
    'applicant_email',
    'applicant_phone',
    'applicant_linkedin',
    'cv_path',
])]
class JobApplication extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Public URL when the CV is stored on the public disk (under /storage/...).
     */
    public function cvPublicUrl(): ?string
    {
        $path = $this->cv_path;
        if ($path === null || $path === '' || str_contains($path, '..')) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
