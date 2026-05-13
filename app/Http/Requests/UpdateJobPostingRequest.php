<?php

namespace App\Http\Requests;

use App\Enums\JobWorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'requirements' => ['sometimes', 'string'],
            'qualification' => ['sometimes', 'string'],
            'location' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(JobWorkType::class)],
        ];
    }
}
