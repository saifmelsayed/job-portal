<?php

namespace App\Http\Requests;

use App\Enums\JobWorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobPostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('approved_disability') || $this->input('approved_disability') === null) {
            $this->merge(['approved_disability' => []]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'requirements' => ['required', 'string'],
            'qualification' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(JobWorkType::class)],
            'approved_disability' => ['required', 'array', 'max:100'],
            'approved_disability.*' => ['distinct', 'string', 'max:255'],
        ];
    }
}
