<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Responses\ApiResponse;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobSeekerSubscriptionPlanController extends Controller
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
}
