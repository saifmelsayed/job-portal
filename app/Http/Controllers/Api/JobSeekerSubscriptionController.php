<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeWithPaymentRequest;
use App\Http\Resources\SubscriptionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobSeekerSubscriptionController extends Controller
{
    public function store(SubscribeWithPaymentRequest $request): JsonResponse
    {
        $user = $request->user();

        if (Subscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Suspended])
            ->exists()) {
            throw ValidationException::withMessages([
                'subscription' => __('You already have an account subscription. Ask an administrator if you need a different plan.'),
            ]);
        }

        $validated = $request->validated();

        /** @var SubscriptionPlan $plan */
        $plan = SubscriptionPlan::query()->findOrFail((int) $validated['subscription_plan_id']);

        $cardLastFour = $request->cardLastFour();

        $holderName = trim((string) ($validated['holder_name'] ?? ''));
        $expiry = (string) ($validated['expiry'] ?? '');

        $subscription = DB::transaction(function () use ($user, $plan, $holderName, $expiry, $cardLastFour): Subscription {
            $sub = Subscription::query()->create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'price_snapshotted' => $plan->price,
                'started_at' => now(),
                'expires_at' => null,
            ]);

            SubscriptionPayment::query()->create([
                'subscription_id' => $sub->getKey(),
                'amount' => $plan->price,
                'holder_name' => $holderName,
                'card_last_four' => $cardLastFour ?? '0000',
                'card_expiry' => $expiry,
                'completed_at' => now(),
            ]);

            return $sub;
        });

        $subscription->load(['plan', 'payment']);

        return ApiResponse::data(
            (new SubscriptionResource($subscription))->resolve($request),
            201,
        );
    }
}
