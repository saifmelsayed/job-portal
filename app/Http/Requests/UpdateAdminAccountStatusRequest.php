<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminAccountStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|\Illuminate\Validation\Rules\In>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['active', 'disabled'])],
        ];
    }
}
