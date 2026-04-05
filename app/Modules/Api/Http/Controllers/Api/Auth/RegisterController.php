<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Api\Http\Requests\Api\RegisterRequest;
use App\Modules\Api\Http\Resources\UserResource;
use App\Modules\Api\Services\Authentication\PersonalAccessTokenService;
use App\Modules\Api\Services\User\UserAccountService;
use Illuminate\Http\JsonResponse;

final class RegisterController extends Controller
{
    public function store(
        RegisterRequest $request,
        UserAccountService $accounts,
        PersonalAccessTokenService $tokens,
    ): JsonResponse {
        $user = $accounts->register($request->safe()->only([
            'name',
            'email',
            'password',
            'avatar',
            'phone',
        ]));

        $result = $tokens->issueForUser($user, $request->input('device_name', 'spa'));

        return response()->json([
            'token' => $result->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($result->user),
        ], 201);
    }
}
