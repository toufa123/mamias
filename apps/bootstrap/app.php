<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // spatie/laravel-permission stopped registering its own middleware
        // aliases when Laravel dropped Http/Kernel.php, so `role:` has to be
        // declared here or the router resolves "role" as a class name and
        // dies with "Target class [role] does not exist" — which is what
        // routes/web.php's `role:super_admin` on mamias/decompose hit.
        //
        // Only `role` is registered because only `role` is used. Add
        // `permission` / `role_or_permission` here if a route ever needs them.
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Behind Plesk's nginx reverse proxy (TLS terminated upstream):
        // trust the proxy's X-Forwarded-* headers so Laravel detects HTTPS
        // and generates correct https:// URLs. TRUSTED_PROXIES defaults to
        // '*' because the container's HTTP port is bound to loopback only,
        // making the proxy the sole possible caller.
        //
        // env() rather than config() is deliberate: this closure runs on
        // afterResolving(HttpKernel), which fires before the kernel bootstraps
        // configuration. The catch is that `config:cache` skips loading .env
        // entirely, so narrowing this to an IP list only takes effect if it is
        // set as a real environment variable — docker-compose.prod.yml does
        // that. A value living only in .env would silently fall back to '*'.
        $trustedProxies = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(
            at: $trustedProxies === '*'
                ? '*'
                : array_map('trim', explode(',', (string) $trustedProxies)),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
