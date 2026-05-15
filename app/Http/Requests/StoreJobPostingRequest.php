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

        if (! $this->has('skills') || $this->input('skills') === null) {
            $this->merge(['skills' => []]);
        }

        $skills = $this->input('skills');
        if (is_array($skills)) {
            $this->merge(['skills' => self::normalizeSkillList($skills)]);
        }

        $category = $this->input('category');
        if (is_string($category)) {
            $this->merge(['category' => trim($category)]);
        }
    }

    /**
     * Shared skill normalization for create/update payloads.
     *
     * @param  array<int, mixed>  $skills
     * @return list<string>
     */
    public static function normalizeSkillList(array $skills): array
    {
        $out = [];
        $seen = [];
        foreach ($skills as $s) {
            if (! is_string($s)) {
                continue;
            }
            $t = mb_substr(trim($s), 0, 100);
            if ($t === '') {
                continue;
            }
            $key = mb_strtolower($t);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $t;
        }

        return $out;
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
            'category' => ['required', 'string', 'max:255'],
            'skills' => ['required', 'array', 'max:50'],
            'skills.*' => ['string', 'max:100'],
        ];
    }
}
