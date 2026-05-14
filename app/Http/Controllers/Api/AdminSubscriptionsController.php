<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubscriptionsController extends Controller
{
    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = Subscription::query()
            ->with(['user', 'plan', 'payment'])
            ->latest('id')
            ->paginate($this->perPage($request));

        $items = collect($paginator->items())
            ->map(fn (Subscription $row) => (new SubscriptionResource($row, includeUser: true))->resolve($request))
            ->values()
            ->all();

        return ApiResponse::data([
            'items' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function suspend(Request $request, Subscription $subscription): JsonResponse
    {
        if (! $subscription->user?->isJobSeeker()) {
            return ApiResponse::message('Subscription not found.', 404);
        }

        if ($subscription->status !== SubscriptionStatus::Active) {
            return ApiResponse::message('Only an active subscription can be suspended.', 422);
        }

        $subscription->update(['status' => SubscriptionStatus::Suspended]);
        $subscription->refresh();
        $subscription->load(['user', 'plan', 'payment']);

        return ApiResponse::data(
            (new SubscriptionResource($subscription, includeUser: true))->resolve($request),
        );
    }
}
