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
        ];
    }
}
