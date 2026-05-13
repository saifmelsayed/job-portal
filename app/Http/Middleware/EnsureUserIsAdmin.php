<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows only users whose role is "admin".
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::message('Unauthenticated.', 401);
        }

        if (! $user->isAdmin()) {
            return ApiResponse::message('Only admin accounts can access this.', 403);
        }

        return $next($request);
    }
}
