<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait HandlesHtmx
{
    public function isHtmx(Request $request): bool
    {
        return $request->hasHeader('HX-Request');
    }

    public function htmxRedirect(string $url): Response
    {
        return new Response('', 200, ['HX-Redirect' => $url]);
    }

    public function htmxTrigger(string $event, string $body = ''): Response
    {
        return new Response($body, 200, ['HX-Trigger' => $event]);
    }
}
