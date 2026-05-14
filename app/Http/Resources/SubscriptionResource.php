<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Subscription
 */
class SubscriptionResource extends JsonResource
{
    public function __construct($resource, protected bool $includeUser = false)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $body = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status->value,
            'price_snapshotted' => (string) $this->price_snapshotted,
            'started_at' => $this->formatDateTime($this->started_at),
            'expires_at' => $this->formatDateTime($this->expires_at),
            'plan' => $this->when(
                $this->relationLoaded('plan') && $this->plan !== null,
                fn (): array => (new SubscriptionPlanResource($this->plan))->resolve($request),
            ),
            'payment' => $this->when(
                $this->relationLoaded('payment') && $this->payment !== null,
                fn (): array => [
                    'amount' => (string) $this->payment->amount,
                    'holder_name' => $this->payment->holder_name,
                    'card_last_four' => $this->payment->card_last_four,
                    'card_expiry' => $this->payment->card_expiry,
                    'completed_at' => $this->formatDateTime($this->payment->completed_at),
                ],
            ),
        ];

        if ($this->includeUser && $this->relationLoaded('user') && $this->user !== null) {
            $body['user'] = [
                'id' => $this->user->id,
                'email' => $this->user->email,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
            ];
        }

        return $body;
    }

    private function formatDateTime(?CarbonInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value
            ->copy()
            ->timezone((string) config('app.timezone', 'Africa/Cairo'))
            ->format('M j, Y \a\t g:i A');
    }
}
