<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'benefits' => ['sometimes', 'array', 'min:1', 'max:100'],
            'benefits.*' => ['required', 'string', 'max:500'],
            'price' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:9999999999.99',
                'regex:/^\d+(?:\.\d{1,2})?$/',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('benefits') || ! is_array($this->input('benefits'))) {
            return;
        }

        $clean = [];
        foreach ($this->input('benefits') as $row) {
            if (is_string($row)) {
                $t = trim($row);
                if ($t !== '') {
                    $clean[] = $t;
                }
            }
        }

        $this->merge(['benefits' => $clean]);
    }
}
