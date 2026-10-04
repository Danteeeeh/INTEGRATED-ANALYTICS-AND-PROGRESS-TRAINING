<?php

use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrackUserActivity;
use App\Http\Middleware\TrustProxies;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            Route::get('/up', function () {
                if (function_exists('fastcgi_finish_request') && PHP_SAPI === 'fpm-fcgi') {
                    fastcgi_finish_request();
                }

                return response('', 204);
            });

            PreventRequestsDuringMaintenance::except(['/up']);

            Route::middleware('api')
                ->prefix('api')
                ->group(__DIR__.'/../routes/api.php');

            Route::middleware('web')
                ->group(__DIR__.'/../routes/web.php');
        },
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Re-enable SecurityHeaders
        $middleware->append(SecurityHeaders::class);

        // Re-enable TrustProxies with default Laravel behavior
        $middleware->replace(
            \Illuminate\Http\Middleware\TrustProxies::class,
            TrustProxies::class
        );

         $middleware->trustHosts(at: [
           '^(.+\.)?bcpsms2\.com$',
           '^localhost$',
           '^127\.0\.0\.1$',
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'permission' => EnsureUserHasPermission::class,
            'activity' => TrackUserActivity::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = 500;
            $message = 'An error occurred.';
            $errors = [];

            if ($e instanceof ValidationException) {
                $status = 422;
                $message = 'Validation failed.';
                $errors = $e->errors();
            } elseif ($e instanceof AuthenticationException) {
                $status = 401;
                $message = 'Unauthenticated.';
            } elseif ($e instanceof AuthorizationException) {
                $status = 403;
                $message = 'Unauthorized.';
            } elseif ($e instanceof ModelNotFoundException) {
                $status = 404;
                $message = 'Resource not found.';
            } elseif ($e instanceof ThrottleRequestsException) {
                $status = 429;
                $message = 'Too many requests.';
            } elseif ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() ?: $message;
            }

            if (! app()->hasDebugModeEnabled() && $status === 500) {
                $message = 'An error occurred.';
            } elseif (app()->hasDebugModeEnabled() && $status === 500) {
                $message = $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], $status);
        });
    })->create();
