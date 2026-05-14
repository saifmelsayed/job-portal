<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreSubscriptionPlanRequest;
use App\Http\Requests\AdminUpdateSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Responses\ApiResponse;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubscriptionPlanController extends Controller
{
    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = SubscriptionPlan::query()
            ->orderBy('price')
            ->paginate($this->perPage($request));

        $items = collect($paginator->items())
            ->map(fn (SubscriptionPlan $plan) => (new SubscriptionPlanResource($plan))->resolve($request))
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

    public function show(Request $request, SubscriptionPlan $subscription_plan): JsonResponse
    {
        return ApiResponse::data(
            (new SubscriptionPlanResource($subscription_plan))->resolve($request),
        );
    }

    public function store(AdminStoreSubscriptionPlanRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::query()->create($request->validated());

        return ApiResponse::data(
            (new SubscriptionPlanResource($plan))->resolve($request),
            201,
        );
    }

    public function update(AdminUpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscription_plan): JsonResponse
    {
        $subscription_plan->update($request->validated());

        return ApiResponse::data(
            (new SubscriptionPlanResource($subscription_plan->fresh()))->resolve($request),
        );
    }

    public function destroy(SubscriptionPlan $subscription_plan): JsonResponse
    {
        if ($subscription_plan->subscriptions()->exists()) {
            return ApiResponse::message(
                'Cannot delete a plan while subscriptions reference it.',
                422,
            );
        }

        $subscription_plan->delete();

        return ApiResponse::message('Plan deleted.');
    }
}
