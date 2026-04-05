<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => __('Already verified.')]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => __('Verification link sent.')]);
    }
}
