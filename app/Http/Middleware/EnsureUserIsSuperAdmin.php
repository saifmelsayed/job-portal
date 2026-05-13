<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows only super admins (they alone can manage other admin accounts later).
 */
class EnsureUserIsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::message('Unauthenticated.', 401);
        }

        if (! $user->isSuperAdmin()) {
            return ApiResponse::message('Only the super admin can do this.', 403);
        }

        return $next($request);
    }
}
