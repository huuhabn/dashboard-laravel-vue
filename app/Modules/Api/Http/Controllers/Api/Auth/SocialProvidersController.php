<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Api\Enums\SocialProvider;
use Illuminate\Http\JsonResponse;

final class SocialProvidersController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => SocialProvider::availabilityMap(),
        ]);
    }
}
