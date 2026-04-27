<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $auth): JsonResponse
    {
        $result = $auth->register($request->validated());

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token']])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request, AuthService $auth): JsonResource|JsonResponse
    {
        $result = $auth->attemptLogin(
            (string) $request->input('email'),
            (string) $request->input('password')
        );

        if ($result === null) {
            return response()->json([
                'message' => trans('auth.failed'),
            ], 401);
        }

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token']]);
    }

    public function logout(Request $request, AuthService $auth): JsonResponse
    {
        $auth->revokeCurrentToken($request->user());

        return response()->json(['message' => 'Logged out']);
    }
}
