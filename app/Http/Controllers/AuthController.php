<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $auth): JsonResponse
    {
        $result = $auth->register($request->validated());

        $user = $result['user']->loadMissing([
            'jobSeekerProfile',
            'companyProfile',
            'admin',
            'skills',
            'educations',
            'experiences',
            'certificates',
        ]);

        return response()->json([
            'message' => 'Registration successful. You can log in now.',
            'data' => (new UserResource($user))->resolve($request),
        ], 201);
    }

    public function login(LoginRequest $request, AuthService $auth): JsonResponse
    {
        $result = $auth->attemptLogin(
            (string) $request->input('email'),
            (string) $request->input('password')
        );

        if ($result === null) {
            return ApiResponse::message(trans('auth.failed'), 401);
        }

        if (isset($result['disabled'])) {
            return ApiResponse::message('This account has been disabled.', 403);
        }

        $result['user']->loadMissing([
            'jobSeekerProfile',
            'companyProfile',
            'admin',
            'skills',
            'educations',
            'experiences',
            'certificates',
        ]);

        return ApiResponse::dataWithToken(
            (new UserResource($result['user']))->resolve($request),
            $result['token'],
        );
    }

    public function adminLogin(LoginRequest $request, AuthService $auth): JsonResponse
    {
        $result = $auth->attemptAdminLogin(
            (string) $request->input('email'),
            (string) $request->input('password')
        );

        if ($result === null) {
            return ApiResponse::message(trans('auth.failed'), 401);
        }

        if (isset($result['disabled'])) {
            return ApiResponse::message('This account has been disabled.', 403);
        }

        $result['user']->loadMissing(['admin']);

        return ApiResponse::dataWithToken(
            (new UserResource($result['user']))->resolve($request),
            $result['token'],
        );
    }

    public function logout(Request $request, AuthService $auth): JsonResponse
    {
        $auth->revokeCurrentToken($request->user());

        return ApiResponse::message('Logged out');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return ApiResponse::message(__($status), 429);
        }

        return ApiResponse::message(
            'If an account exists for that email, we sent a password reset link.',
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::message(__($status), 422);
        }

        return ApiResponse::message(__($status));
    }
}
