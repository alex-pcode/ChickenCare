<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremiumTier
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->isPremium()) {
            return $next($request);
        }

        if ($request->header('HX-Request')) {
            return response()->view('partials.premium-gate', [
                'feature' => $this->resolveFeatureLabel($request),
            ]);
        }

        return redirect()->route('app.dashboard')
            ->with('warning', __('premium.redirect_warning'));
    }

    private function resolveFeatureLabel(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if (! is_string($routeName)) {
            return null;
        }

        return match (true) {
            str_starts_with($routeName, 'app.flock.') => __('navigation.premium.flock'),
            str_starts_with($routeName, 'app.batches.') => __('navigation.premium.batches'),
            str_starts_with($routeName, 'app.crm.') => __('navigation.premium.crm'),
            str_starts_with($routeName, 'app.expenses.') => __('navigation.premium.expenses'),
            str_starts_with($routeName, 'app.feed.') => __('navigation.premium.feed'),
            str_starts_with($routeName, 'app.savings.') => __('navigation.premium.savings'),
            str_starts_with($routeName, 'app.viability.') => __('navigation.premium.viability'),
            default => null,
        };
    }
}
