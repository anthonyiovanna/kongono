<?php

declare(strict_types=1);

use App\Http\HomeController;
use Kongono\Http\App;
use Kongono\Security\Middleware\SecurityHeadersMiddleware;

return App::create(dirname(__DIR__), name: 'My App')
    ->config(['app' => ['debug' => true]])
    ->views()
    ->middleware(new SecurityHeadersMiddleware())
    ->controller(HomeController::class);
