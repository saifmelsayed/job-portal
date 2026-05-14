<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyProfileRequest extends FormRequest
{
    private const PROFILE_PHOTO_MAX_KB = 2048;

    public function authorize(): bool
    {
        return $this->user()?->isCompany() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'company_name' => ['sometimes', 'string', 'max:255'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'company_size' => ['sometimes', 'nullable', 'string', 'max:50'],
            'disability_support_policy' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'overview' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'facebook_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'x_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'linkedin_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'instagram_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'street' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],

            'profile_photo' => [
                'sometimes',
                'file',
                'image',
                'max:'.self::PROFILE_PHOTO_MAX_KB,
                'extensions:jpg,jpeg,png,webp,gif',
            ],

            'clear_profile_photo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        $merge = [
            'phone' => ($phone === '' || $phone === null) ? null : $phone,
        ];

        foreach (
            ['overview', 'facebook_url', 'x_url', 'linkedin_url', 'instagram_url'] as $key
        ) {
            $value = $this->input($key);
            if (is_string($value) && trim($value) === '') {
                $merge[$key] = null;
            }
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profile_photo.image' => 'The profile photo must be an image file.',
            'profile_photo.extensions' => 'Allowed types: JPG, PNG, WEBP, or GIF.',
        ];
    }
}
