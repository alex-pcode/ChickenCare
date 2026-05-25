<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Traits\HandlesHtmx;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Password;

class AccountController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request): View
    {
        $validTabs = ['profile', 'security', 'billing', 'goals'];
        $tab = in_array($request->query('tab'), $validTabs, true) ? $request->query('tab') : 'profile';
        $user = $request->user();

        $data = compact('tab', 'user');

        if ($tab === 'goals') {
            $data = [...$data, ...$this->goalsTabData($user)];
        }

        if ($this->isHtmx($request) && ! $request->hasHeader('HX-Boosted')) {
            return view("account.partials.tab-{$tab}", $data);
        }

        return view('account.index', $data);
    }

    public function skeleton(): Response
    {
        return response()->view('account.index', [
            'skel' => true,
            'tab' => 'profile',
            'user' => auth()->user(),
        ])->header('Cache-Control', 'private, max-age=300');
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse|Response
    {
        $request->user()->update($request->validated());

        if ($this->isHtmx($request)) {
            $user = $request->user()->fresh();
            $tab = 'profile';

            return response()
                ->view('account.partials.tab-profile', compact('user', 'tab'))
                ->header('HX-Trigger', 'account-profile-updated');
        }

        return redirect()->route('app.account.index')
            ->with('success', __('account.messages.profile_updated'));
    }

    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse|Response
    {
        $user = $request->user();
        $validated = $request->validated();
        $selectedLocale = $validated['locale'] ?? null;
        $localeChanged = is_string($selectedLocale) && $selectedLocale !== app()->getLocale();

        $user->update($validated);

        if ($localeChanged) {
            $this->persistBrowserLocale($request, $selectedLocale);
        }

        if ($this->isHtmx($request)) {
            if ($localeChanged) {
                return $this->htmxRedirect(route('app.account.index', ['tab' => 'goals']));
            }

            $user = $user->fresh();
            $tab = 'goals';

            return response()
                ->view('account.partials.tab-goals', [...compact('user', 'tab'), ...$this->goalsTabData($user)])
                ->header('HX-Trigger', 'account-preferences-updated');
        }

        if (is_string($selectedLocale) && $selectedLocale !== '') {
            app()->setLocale($selectedLocale);
            $request->setLocale($selectedLocale);
        }

        return redirect()->route('app.account.index', ['tab' => 'goals'])
            ->with('success', __('account.messages.preferences_updated'));
    }

    public function sendPasswordResetLink(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($this->isHtmx($request)) {
            if ($status === Password::RESET_LINK_SENT) {
                return $this->htmxTrigger('account-password-reset-sent');
            }

            return response('', 200, [
                'HX-Trigger' => json_encode([
                    'account-password-reset-failed' => ['message' => __($status)],
                ]),
            ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->route('app.account.index', ['tab' => 'security'])
                ->with('success', __('account.messages.password_reset_sent'));
        }

        return redirect()->route('app.account.index', ['tab' => 'security'])
            ->with('error', __($status));
    }

    /**
     * @return array<string, int|bool>
     */
    private function goalsTabData(User $user): array
    {
        return [
            'yearProgress' => (int) $user->eggEntries()
                ->whereYear('date', now()->year)
                ->sum('count'),
            'thisMonthEggs' => (int) $user->eggEntries()
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('count'),
            'thisWeekEggs' => (int) $user->eggEntries()
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum('count'),
            'hasEggEntries' => $user->eggEntries()->exists(),
        ];
    }

    private function persistBrowserLocale(Request $request, ?string $locale): void
    {
        if (! is_string($locale) || $locale === '') {
            return;
        }

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        Cookie::queue(cookie()->forever(config('app.locale_cookie', 'chickencare_locale'), $locale));
    }
}
