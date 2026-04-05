<?php

declare(strict_types=1);

namespace App\Modules\Api\Providers;

use App\Modules\Api\Contracts\LoginOtpSmsSenderContract;
use App\Modules\Api\Repositories\Contracts\UserRepositoryContract;
use App\Modules\Api\Repositories\UserRepository;
use App\Modules\Api\Services\Authentication\LogLoginOtpSmsSender;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryContract::class, UserRepository::class);
        $this->app->bind(LoginOtpSmsSenderContract::class, LogLoginOtpSmsSender::class);
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
