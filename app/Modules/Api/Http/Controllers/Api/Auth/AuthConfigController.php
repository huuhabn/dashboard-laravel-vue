<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Shared\Saas\SaasAuth;
use Illuminate\Http\JsonResponse;

/**
 * Public SaaS auth toggles for the SPA (login method, OTP channel, default phone region).
 */
final class AuthConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => SaasAuth::publicAuthPayload(),
        ]);
    }
}
