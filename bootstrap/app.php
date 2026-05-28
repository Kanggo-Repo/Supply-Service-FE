<?php

use App\Http\Middleware\EnsurePlatformAuthenticated;
use App\Http\Middleware\EnsureServiceAccess;
use App\Http\Middleware\EnsureSupplyPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'platform.auth' => EnsurePlatformAuthenticated::class,
            'service.access' => EnsureServiceAccess::class,
            'supply.permission' => EnsureSupplyPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
