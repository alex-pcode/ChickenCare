<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SetDynamicResponseCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldSkip($response)) {
            return $response;
        }

        $response->headers->remove('ETag');
        $response->headers->remove('Last-Modified');

        // Boosted GET navigations may be briefly cached so an hover/touch prefetch
        // (see window.ChickenCare.prefetch in app.js) can serve the next navigation
        // from the browser cache instead of a fresh server round-trip. Vary on
        // HX-Request keeps direct, non-boosted page loads on the no-store path.
        if ($request->isMethod('GET') && $request->headers->get('HX-Boosted') === 'true') {
            $response->headers->set('Cache-Control', 'private, max-age=5');
            $response->headers->set('Vary', 'HX-Request', false);

            return $response;
        }

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function shouldSkip(Response $response): bool
    {
        return $response instanceof BinaryFileResponse || $response instanceof StreamedResponse;
    }
}
