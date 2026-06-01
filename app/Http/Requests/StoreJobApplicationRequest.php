<?php

namespace App\Http\Requests;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;

class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isJobSeeker() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if ($user === null) {
                return;
            }

            $name = self::applicantDisplayNameFromUser($user);
            if ($name === '') {
                $validator->errors()->add(
                    'profile',
                    'Add your full name on your account before applying.',
                );
            }

            $phone = trim((string) ($user->phone ?? ''));
            if ($phone === '') {
                $validator->errors()->add(
                    'profile',
                    'Add a phone number on your account before applying.',
                );
            }

            $user->loadMissing('jobSeekerProfile');
            $cvPath = $user->jobSeekerProfile?->cv_path;
            if (! is_string($cvPath) || $cvPath === '' || ! Storage::disk('public')->exists($cvPath)) {
                $validator->errors()->add(
                    'profile',
                    'Upload a CV on your profile before applying.',
                );
            }

            $jobPosting = $this->route('job_posting');
            if ($jobPosting === null) {
                return;
            }

            if (JobApplication::query()
                ->where('job_posting_id', $jobPosting->id)
                ->where('user_id', $user->id)
                ->exists()) {
                $validator->errors()->add(
                    'job_posting',
                    'You have already applied to this job.',
                );
            }
        });
    }

    public static function applicantDisplayNameFromUser(User $user): string
    {
        $fullName = is_string($user->full_name) ? trim($user->full_name) : '';

        return $fullName;
    }
}
