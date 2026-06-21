<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Emits a `Server-Timing` header with total PHP wall time so server-side
 * processing can be measured separately from network latency in the browser.
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        if (defined('LARAVEL_START')) {
            $durationMs = (microtime(true) - LARAVEL_START) * 1000;
            $response->headers->set('Server-Timing', sprintf('app;desc="PHP wall time";dur=%.1f', $durationMs));
        }

        return $response;
    }
}
