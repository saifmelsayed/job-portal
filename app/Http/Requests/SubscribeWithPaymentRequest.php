<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SubscribeWithPaymentRequest extends FormRequest
{
    private const SIMULATED_CARD_DIGIT_LENGTH = 16;
    public function authorize(): bool
    {
        return $this->user()?->isJobSeeker() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'holder_name' => ['required', 'string', 'max:255'],
            'card_number' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }
                    $digits = preg_replace('/\D/', '', $value) ?? '';
                    if (strlen($digits) !== self::SIMULATED_CARD_DIGIT_LENGTH) {
                        $fail(__('The card number must be exactly :count digits.', [
                            'count' => self::SIMULATED_CARD_DIGIT_LENGTH,
                        ]));
                    }
                },
            ],
            'expiry' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv' => ['required', 'string', 'regex:/^\d{3,4}$/'],
        ];
    }

    public function sanitizedCardDigits(): ?string
    {
        $value = $this->input('card_number');
        if (! is_string($value)) {
            return null;
        }

        return preg_replace('/\D/', '', $value);
    }

    /** Last four digits for storage (never persist full PAN or CVV). */
    public function cardLastFour(): ?string
    {
        $digits = $this->sanitizedCardDigits();

        return is_string($digits) && strlen($digits) >= 4
            ? substr($digits, -4)
            : null;
    }
}
