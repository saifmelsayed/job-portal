<?php

namespace App\Http\Requests;

use App\Models\JobApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isJobSeeker() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
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
            $email = $this->input('email');

            if ($user === null || ! is_string($email)) {
                return;
            }

            if ($email !== strtolower((string) $user->email)) {
                $validator->errors()->add(
                    'email',
                    'The email must match your account email.',
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
}
