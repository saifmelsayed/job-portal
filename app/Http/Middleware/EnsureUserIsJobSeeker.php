<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsJobSeeker
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::message('Unauthenticated.', 401);
        }

        if (! $user->isJobSeeker()) {
            return ApiResponse::message('Only job seeker accounts can access this resource.', 403);
        }

        return $next($request);
    }
}
