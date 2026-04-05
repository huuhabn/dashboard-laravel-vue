<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Api\Enums\SocialProvider;
use App\Modules\Api\Http\Resources\UserResource;
use App\Modules\Api\Services\Social\SocialLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SocialAccountController extends Controller
{
    public function destroy(
        Request $request,
        string $provider,
        SocialLoginService $social,
    ): JsonResponse {
        $socialProvider = SocialProvider::tryFromRoute($provider);

        if ($socialProvider === null) {
            abort(404);
        }

        try {
            $social->unlinkProvider($request->user(), $socialProvider);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            throw ValidationException::withMessages([
                'provider' => [$e->getMessage()],
            ]);
        }

        return response()->json([
            'message' => 'Social account unlinked.',
            'user' => new UserResource($request->user()->fresh()),
        ]);
    }
}
