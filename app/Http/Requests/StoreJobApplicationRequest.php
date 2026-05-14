<?php

namespace App\Http\Requests;

use App\Models\JobApplication;
use App\Models\JobSeekerProfile;
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
     * Uses job seeker account + profile when the client does not upload a CV and does not send name/email.
     * Send `from_profile: false` to require the classic apply payload (name, email, phone, cv file).
     */
    public function applyFromProfile(): bool
    {
        if ($this->has('from_profile') && ! $this->boolean('from_profile')) {
            return false;
        }

        if ($this->boolean('from_profile')) {
            return true;
        }

        if ($this->hasFile('cv')) {
            return false;
        }

        if ($this->filled('name') || $this->filled('email')) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->applyFromProfile()) {
            return [
                'from_profile' => ['sometimes', 'boolean'],
                'linkedin' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'linkedin' => ['nullable', 'string', 'url', 'max:2048'],
            'cv' => [
                'required',
                'file',
                'max:5120',
                'mimes:pdf,doc,docx',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cv.mimes' => 'The CV must be a PDF or Word document (.pdf, .doc, .docx).',
            'cv.max' => 'The CV may not be larger than 5 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $linkedin = $this->input('linkedin');
        $this->merge([
            'email' => is_string($email) ? strtolower(trim($email)) : $email,
            'linkedin' => is_string($linkedin) && trim($linkedin) === '' ? null : $linkedin,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if ($user === null) {
                return;
            }

            if (! $this->applyFromProfile()) {
                $email = $this->input('email');
                if (! is_string($email)) {
                    return;
                }

                if ($email !== strtolower((string) $user->email)) {
                    $validator->errors()->add(
                        'email',
                        'The email must match your account email.',
                    );
                }
            } else {
                $user->loadMissing('jobSeekerProfile');
                $profile = $user->jobSeekerProfile;
                $name = self::applicantDisplayNameFromUser($user, $profile);
                if ($name === '') {
                    $validator->errors()->add(
                        'profile',
                        'Add your full name on your profile before applying.',
                    );
                }
                $phone = trim((string) ($user->phone ?? ''));
                if ($phone === '') {
                    $validator->errors()->add(
                        'profile',
                        'Add a phone number on your account before applying.',
                    );
                }
                $cvPath = $profile?->cv_path;
                if (! is_string($cvPath) || $cvPath === '' || ! Storage::disk('public')->exists($cvPath)) {
                    $validator->errors()->add(
                        'profile',
                        'Upload a CV on your profile before applying.',
                    );
                }
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

    /** Display name resolved from profile and user fields (same rules as backend apply-from-profile behavior). */
    public static function applicantDisplayNameFromUser(User $user, ?JobSeekerProfile $profile): string
    {
        if ($profile !== null && is_string($profile->full_name) && trim($profile->full_name) !== '') {
            return trim($profile->full_name);
        }

        $fromParts = trim(implode(' ', array_filter([
            $profile !== null ? (string) $profile->first_name : '',
            $profile !== null ? (string) $profile->last_name : '',
        ], static fn (string $p): bool => trim($p) !== '')));

        if ($fromParts !== '') {
            return $fromParts;
        }

        return trim(implode(' ', array_filter([(string) $user->first_name, (string) $user->last_name], static fn (string $p): bool => trim($p) !== '')));
    }
}
