<?php

declare(strict_types=1);

namespace App\Modules\Api\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Api\Services\Dashboard\GetDashboardOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function show(
        Request $request,
        GetDashboardOverviewService $service,
    ): JsonResponse {
        return response()->json($service->handle($request->user()->name));
    }
}
