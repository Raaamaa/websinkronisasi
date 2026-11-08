<?php

declare(strict_types=1);

use App\Http\Middleware\AuthorizePermission;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: [
            '172.16.0.0/12',
            '10.0.0.0/8',
            '192.168.0.0/16',
            '127.0.0.1',
        ]);

        $middleware->redirectGuestsTo('/login');

        $middleware->web(append: [
            EnsureAccountIsActive::class,
        ]);

        $middleware->alias([
            'active' => EnsureAccountIsActive::class,
            'password.changed' => EnsurePasswordChanged::class,
            'permission' => AuthorizePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $e, Request $request) {
            $msg = $e->getMessage();
            if (
                str_contains($msg, 'could not translate host name') ||
                str_contains($msg, 'Connection refused') ||
                str_contains($msg, 'SQLSTATE[08006]') ||
                str_contains($msg, 'server closed the connection')
            ) {
                Log::critical('Database connection failure: '.$msg);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Layanan basis data sedang tidak dapat dijangkau. Silakan hubungi administrator.',
                    ], 503);
                }

                if ($request->is('login') || $request->is('auth/*')) {
                    return redirect()->route('login')->with(
                        'error',
                        'Layanan basis data sedang tidak dapat dijangkau. Pastikan container basis data (PostgreSQL) berjalan.'
                    );
                }

                return response('Layanan basis data sedang tidak dapat dijangkau. Pastikan container basis data (PostgreSQL) berjalan.', 503);
            }

            return null;
        });
    })->create();
