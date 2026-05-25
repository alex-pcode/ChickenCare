<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ResolveLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        $request->setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supportedLocales = $this->supportedLocales();
        $fallbackLocale = config('app.fallback_locale', 'en');

        if (! in_array($fallbackLocale, $supportedLocales, true)) {
            $fallbackLocale = 'en';
        }

        $candidates = [
            $request->user()?->locale,
            $request->hasSession() ? $request->session()->get('locale') : null,
            $request->cookie(config('app.locale_cookie', 'chickencare_locale')),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supportedLocales, true)) {
                return $candidate;
            }
        }

        return $fallbackLocale;
    }

    /**
     * @return array<int, string>
     */
    private function supportedLocales(): array
    {
        $supportedLocales = config('app.supported_locales', ['en']);

        return array_values(array_filter(
            is_array($supportedLocales) ? $supportedLocales : ['en'],
            static fn (mixed $locale): bool => is_string($locale) && $locale !== ''
        ));
    }
}
