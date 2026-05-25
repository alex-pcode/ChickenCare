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

        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->remove('ETag');
        $response->headers->remove('Last-Modified');

        return $response;
    }

    private function shouldSkip(Response $response): bool
    {
        return $response instanceof BinaryFileResponse || $response instanceof StreamedResponse;
    }
}
