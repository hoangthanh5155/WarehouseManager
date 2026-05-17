<?php

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
            'can.manage.users' => \App\Http\Middleware\EnsureUserCanManageUsers::class,
            'admin.only' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'active.user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
