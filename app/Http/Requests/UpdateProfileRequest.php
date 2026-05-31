<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    private const PROFILE_CV_MAX_KB = 5120;

    private const PROFILE_PHOTO_MAX_KB = 2048;

    public function authorize(): bool
    {
        return $this->user()?->isJobSeeker() ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['skills', 'educations', 'experiences', 'certificates'] as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);
            if ($trimmed === '') {
                $this->merge([$field => []]);

                continue;
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge([$field => $decoded]);
            }
        }

        $phone = $this->input('phone');
        $this->merge([
            'phone' => ($phone === '' || $phone === null) ? null : $phone,
        ]);

        foreach (['full_name'] as $field) {
            $value = $this->input($field);
            if (! is_string($value)) {
                continue;
            }
            $trimmed = trim($value);
            $this->merge([$field => $trimmed === '' ? null : $trimmed]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'first_name' => ['prohibited'],
            'last_name' => ['prohibited'],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'gender' => ['sometimes', 'nullable', 'string', 'max:20'],
            'disability_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],

            'profile_photo' => [
                'sometimes',
                'file',
                'image',
                'max:'.self::PROFILE_PHOTO_MAX_KB,
                'extensions:jpg,jpeg,png,webp,gif',
            ],
            'clear_profile_photo' => ['sometimes', 'boolean'],

            'skills' => ['sometimes', 'array', 'max:50'],
            'skills.*' => ['string', 'max:100'],

            'educations' => ['sometimes', 'array', 'max:30'],
            'educations.*.institution' => ['required', 'string', 'max:255'],
            'educations.*.degree' => ['nullable', 'string', 'max:255'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:255'],
            'educations.*.starts_at' => ['nullable', 'date'],
            'educations.*.ends_at' => ['nullable', 'date'],
            'educations.*.details' => ['nullable', 'string', 'max:5000'],

            'experiences' => ['sometimes', 'array', 'max:30'],
            'experiences.*.company_name' => ['required', 'string', 'max:255'],
            'experiences.*.title' => ['required', 'string', 'max:255'],
            'experiences.*.starts_at' => ['nullable', 'date'],
            'experiences.*.ends_at' => ['nullable', 'date'],
            'experiences.*.description' => ['nullable', 'string', 'max:8000'],

            'certificates' => ['sometimes', 'array', 'max:30'],
            'certificates.*.name' => ['required', 'string', 'max:255'],
            'certificates.*.issuer' => ['nullable', 'string', 'max:255'],
            'certificates.*.issued_at' => ['nullable', 'date'],
            'certificates.*.credential_url' => ['nullable', 'url', 'max:2048'],

            'cv' => [
                'sometimes',
                'file',
                'max:'.self::PROFILE_CV_MAX_KB,
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'extensions:pdf,doc,docx',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cv.extensions' => 'The CV must be a .pdf, .doc, or .docx file.',
            'cv.mimetypes' => 'The CV must be a PDF or Word document (.pdf, .doc, .docx).',
            'cv.mimes' => 'The CV must be a PDF or Word document (.pdf, .doc, .docx).',
            'cv.max' => 'The CV may not be larger than 5 MB.',
            'profile_photo.image' => 'The profile photo must be an image file.',
            'profile_photo.extensions' => 'Allowed types: JPG, PNG, WEBP, or GIF.',
        ];
    }
}
