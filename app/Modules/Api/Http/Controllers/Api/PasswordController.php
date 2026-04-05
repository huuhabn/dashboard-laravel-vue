<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Api\Http\Requests\Api\PasswordUpdateRequest;
use App\Modules\Api\Services\User\UserAccountService;
use Illuminate\Http\JsonResponse;

final class PasswordController extends Controller
{
    public function update(
        PasswordUpdateRequest $request,
        UserAccountService $accounts,
    ): JsonResponse {
        $accounts->changePassword($request->user(), $request->validated('password'));

        return response()->json(['message' => 'Password updated.']);
    }
}
