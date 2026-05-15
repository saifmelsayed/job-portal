<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyAccountRequest;
use App\Http\Responses\ApiResponse;
use App\Services\UserAccountDeletionService;
use Illuminate\Http\JsonResponse;

class AccountDeletionController extends Controller
{
    public function destroy(DestroyAccountRequest $request, UserAccountDeletionService $deletion): JsonResponse
    {
        $deletion->delete($request->user());

        return ApiResponse::message('Your account has been deleted.');
    }
}
