<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<int, string|Rule>|string>
     */
    public function rules(): array
    {
        $jobSeeker = UserRole::JobSeeker->value;
        $company = UserRole::Company->value;

        return [
            'first_name' => ['prohibited'],
            'last_name' => ['prohibited'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],

            'full_name' => [
                "prohibited_if:role,{$company}",
                "required_if:role,{$jobSeeker}",
                'string',
                'max:255',
            ],
            'skills' => [
                "prohibited_if:role,{$company}",
                "required_if:role,{$jobSeeker}",
                'array',
                'min:1',
            ],
            'skills.*' => [
                'string',
                'max:100',
            ],
            'cv_path' => [
                "prohibited_if:role,{$company}",
                'nullable',
                'string',
                'max:500',
            ],

            'company_name' => [
                "prohibited_if:role,{$jobSeeker}",
                "required_if:role,{$company}",
                'string',
                'max:255',
            ],
            'industry' => [
                "prohibited_if:role,{$jobSeeker}",
                "required_if:role,{$company}",
                'string',
                'max:100',
            ],
            'company_size' => [
                "prohibited_if:role,{$jobSeeker}",
                "required_if:role,{$company}",
                'string',
                'max:50',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $phone = $this->input('phone');
        $this->merge([
            'email' => is_string($email) ? strtolower($email) : $email,
            'phone' => ($phone === '' || $phone === null) ? null : $phone,
        ]);
    }
}
