<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks non-admin accounts whose status is not "active" (e.g. disabled by an admin).
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::message('Unauthenticated.', 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->status !== 'active') {
            return ApiResponse::message('This account has been disabled.', 403);
        }

        return $next($request);
    }
}
