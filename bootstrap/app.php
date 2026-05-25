<?php

use App\Http\Middleware\DetectHtmx;
use App\Http\Middleware\EnsurePremiumTier;
use App\Http\Middleware\ResolveLocale;
use App\Http\Middleware\SetDynamicResponseCacheHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->web(prepend: [
            SetDynamicResponseCacheHeaders::class,
        ]);
        $middleware->web(append: [
            ResolveLocale::class,
        ]);
        $middleware->append(DetectHtmx::class);
        $middleware->alias([
            'premium' => EnsurePremiumTier::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (ValidationException $e, $request) {
            if ($request->hasHeader('HX-Request')) {
                $errors = $e->validator->errors();
                $html = '<div class="form-errors" role="alert" aria-live="assertive">';
                foreach ($errors->all() as $message) {
                    $html .= '<p class="form-error">'.e($message).'</p>';
                }
                $html .= '</div>';

                return response($html, 422);
            }
        });
    })->create();
