<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestOrJobSeeker
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if ($plain === null || $plain === '') {
            return $next($request);
        }

        $accessToken = PersonalAccessToken::findToken($plain);

        if ($accessToken === null) {
            return ApiResponse::message('Unauthenticated.', 401);
        }

        $user = $accessToken->tokenable;

        if (! $user->isJobSeeker() && ! $user->isAdmin()) {
            return ApiResponse::message('Only guests and job seekers can browse job postings.', 403);
        }

        if ($user->isJobSeeker() && $user->status !== 'active') {
            return ApiResponse::message('This account has been disabled.', 403);
        }

        auth()->guard('sanctum')->setUser($user);

        return $next($request);
    }
}
