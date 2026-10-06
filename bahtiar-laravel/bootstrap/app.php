<?php

use App\Http\Middleware\PeranAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'peran.admin' => PeranAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Sumber daya tidak ditemukan',
                ], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $header = $e->getHeaders();

                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Metode HTTP tidak diizinkan untuk endpoint ini',
                    'diizinkan' => array_map(
                        'trim',
                        explode(',', $header['Allow'] ?? '')
                    ),
                ], 405);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Data yang dikirim tidak valid',
                    'galat' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Token tidak valid atau belum dikirim',
                ], 401);
            }
        });

        $exceptions->render(function (\Laravel\Sanctum\Exceptions\MissingAbilityException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => 'Token tidak memiliki kemampuan yang cukup',
                ], 403);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'sukses' => false,
                    'pesan' => $e->getMessage() ?: 'Akses ditolak',
                ], 403);
            }
        });
    })->create();
