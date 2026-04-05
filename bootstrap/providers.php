<?php

use App\Modules\Api\Providers\ModuleServiceProvider as ApiModuleServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    ApiModuleServiceProvider::class,
];
