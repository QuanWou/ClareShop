<?php

use App\Modules\Cart\Http\Middleware\ShareCartSummary;
use App\Modules\Customers\Http\Middleware\EnsureUserIsActive;
use App\Modules\Customers\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/paypal',
            'webhooks/momo',
            'webhooks/payos',
        ]);
        $middleware->appendToGroup('web', ShareCartSummary::class);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'active-user' => EnsureUserIsActive::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => route('account.show'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
