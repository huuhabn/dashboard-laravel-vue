<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Api\Http\Requests\Api\AvatarUploadRequest;
use App\Modules\Api\Http\Requests\Api\ProfileDeleteRequest;
use App\Modules\Api\Http\Requests\Api\ProfileUpdateRequest;
use App\Modules\Api\Http\Resources\UserResource;
use App\Modules\Api\Services\User\UserAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class AccountController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(
        ProfileUpdateRequest $request,
        UserAccountService $accounts,
    ): UserResource {
        $user = $accounts->updateProfile($request->user(), $request->validated());

        return new UserResource($user);
    }

    public function uploadAvatar(
        AvatarUploadRequest $request,
        UserAccountService $accounts,
    ): UserResource {
        /** @var UploadedFile $file */
        $file = $request->file('avatar');

        return new UserResource($accounts->uploadAvatar($request->user(), $file));
    }

    public function destroy(
        ProfileDeleteRequest $request,
        UserAccountService $accounts,
    ): JsonResponse {
        $accounts->deleteAccount($request->user());

        return response()->json(null, 204);
    }
}
