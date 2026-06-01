<?php

namespace App\Http\Requests;

use App\Enums\JobPostingStatus;
use App\Enums\JobWorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $category = $this->input('category');
        if (is_string($category)) {
            $this->merge(['category' => trim($category)]);
        }

        if (! $this->has('skills')) {
            return;
        }

        $skills = $this->input('skills');
        if (is_array($skills)) {
            $this->merge(['skills' => StoreJobPostingRequest::normalizeSkillList($skills)]);
        }
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
            'approved_disability' => ['sometimes', 'array', 'max:100'],
            'approved_disability.*' => ['distinct', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'skills' => ['sometimes', 'array', 'max:50'],
            'skills.*' => ['string', 'max:100'],
            'status' => ['sometimes', Rule::enum(JobPostingStatus::class)],
        ];
    }
}
