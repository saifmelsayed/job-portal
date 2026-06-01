<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'job_posting_id',
    'user_id',
    'status',
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
     * Eager-load keys for listing application details.
     *
     * @return list<string>
     */
    public static function applicantProfileWith(): array
    {
        return [
            'jobPosting',
            'jobPosting.user.companyProfile',
            'applicant',
            'applicant.jobSeekerProfile',
            'applicant.skills',
            'applicant.educations',
            'applicant.experiences',
            'applicant.certificates',
        ];
    }
}
