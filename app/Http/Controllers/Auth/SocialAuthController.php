<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SocialiteRedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    private const SUPPORTED_PROVIDERS = [
        'google',
        'facebook',
    ];

    public function redirect(string $provider): SocialiteRedirectResponse|RedirectResponse
    {
        abort_unless($this->isSupportedProvider($provider), 404);

        if (! $this->providerIsConfigured($provider)) {
            return to_route('login')->with('auth_error', __('auth.social.errors.not_configured', ['provider' => $this->providerLabel($provider)]));
        }

        $driver = Socialite::driver($provider);

        if ($provider === 'google') {
            return $driver->scopes(['openid', 'profile', 'email'])->redirect();
        }

        return $driver->scopes(['email'])->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless($this->isSupportedProvider($provider), 404);

        if (! $this->providerIsConfigured($provider)) {
            return to_route('login')->with('auth_error', __('auth.social.errors.not_configured', ['provider' => $this->providerLabel($provider)]));
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return to_route('login')->with('auth_error', __('auth.social.errors.unable_to_authenticate', ['provider' => $this->providerLabel($provider)]));
        }

        $email = Str::lower((string) $providerUser->getEmail());

        if ($email === '') {
            return to_route('register')->with('auth_error', __('auth.social.errors.email_missing', ['provider' => $this->providerLabel($provider)]));
        }

        [$user, $created] = DB::transaction(function () use ($provider, $providerUser, $email) {
            return $this->resolveUser($provider, $providerUser, $email);
        });

        if ($created) {
            event(new Registered($user));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('app.dashboard', absolute: false));
    }

    private function resolveUser(string $provider, ProviderUser $providerUser, string $email): array
    {
        $socialAccount = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUser->getId())
            ->first();

        if ($socialAccount !== null) {
            $this->updateSocialAccount($socialAccount, $providerUser, $email);

            return [$socialAccount->user, false];
        }

        $user = User::query()->firstWhere('email', $email);
        $created = false;

        if ($user === null) {
            $user = User::query()->create([
                'name' => $providerUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Str::password(32),
            ]);

            $created = true;
        } elseif ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        SocialAccount::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'provider_user_id' => $providerUser->getId(),
                'provider_email' => $email,
                'avatar' => $providerUser->getAvatar(),
            ],
        );

        return [$user, $created];
    }

    private function updateSocialAccount(SocialAccount $socialAccount, ProviderUser $providerUser, string $email): void
    {
        $socialAccount->fill([
            'provider_email' => $email,
            'avatar' => $providerUser->getAvatar(),
        ])->save();

        if ($socialAccount->user->email_verified_at === null) {
            $socialAccount->user->forceFill(['email_verified_at' => now()])->save();
        }
    }

    private function isSupportedProvider(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS, true);
    }

    private function providerIsConfigured(string $provider): bool
    {
        return filled(config('services.'.$provider.'.client_id'))
            && filled(config('services.'.$provider.'.client_secret'))
            && filled(config('services.'.$provider.'.redirect'));
    }

    private function providerLabel(string $provider): string
    {
        return __('auth.social.providers.'.$provider);
    }
}
