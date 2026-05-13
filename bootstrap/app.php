<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureGuestOrJobSeeker;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCompany;
use App\Http\Middleware\EnsureUserIsJobSeeker;
use App\Http\Middleware\EnsureUserIsSuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account_active' => EnsureAccountIsActive::class,
            'admin' => EnsureUserIsAdmin::class,
            'super_admin' => EnsureUserIsSuperAdmin::class,
            'company' => EnsureUserIsCompany::class,
            'job_seeker' => EnsureUserIsJobSeeker::class,
            'guest_or_job_seeker' => EnsureGuestOrJobSeeker::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
