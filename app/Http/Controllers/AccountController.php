<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePreferencesRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Traits\HandlesHtmx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AccountController extends Controller
{
    use HandlesHtmx;

    public function index(Request $request)
    {
        $validTabs = ['profile', 'security', 'billing', 'goals'];
        $tab = in_array($request->query('tab'), $validTabs, true) ? $request->query('tab') : 'profile';
        $user = $request->user();

        $data = compact('tab', 'user');

        if ($tab === 'goals') {
            $data['yearProgress'] = (int) $user->eggEntries()
                ->whereYear('date', now()->year)
                ->sum('count');
            $data['thisMonthEggs'] = (int) $user->eggEntries()
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('count');
            $data['thisWeekEggs'] = (int) $user->eggEntries()
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum('count');
            $data['hasEggEntries'] = $user->eggEntries()->exists();
        }

        if ($this->isHtmx($request) && !$request->hasHeader('HX-Boosted')) {
            return view("account.partials.tab-{$tab}", $data);
        }

        return view('account.index', $data);
    }

    public function updateProfile(UpdateProfileRequest $request)
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
            ->with('success', 'Profile updated successfully!');
    }

    public function updatePreferences(UpdatePreferencesRequest $request)
    {
        $request->user()->update($request->validated());

        if ($this->isHtmx($request)) {
            $user = $request->user()->fresh();
            $tab = 'goals';

            $yearProgress = (int) $user->eggEntries()
                ->whereYear('date', now()->year)
                ->sum('count');
            $thisMonthEggs = (int) $user->eggEntries()
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('count');
            $thisWeekEggs = (int) $user->eggEntries()
                ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum('count');
            $hasEggEntries = $user->eggEntries()->exists();

            return response()
                ->view('account.partials.tab-goals', compact('user', 'tab', 'yearProgress', 'thisMonthEggs', 'thisWeekEggs', 'hasEggEntries'))
                ->header('HX-Trigger', 'account-preferences-updated');
        }

        return redirect()->route('app.account.index', ['tab' => 'goals'])
            ->with('success', 'Preferences updated successfully!');
    }

    public function sendPasswordResetLink(Request $request)
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
                ->with('success', 'Password reset link sent to your email!');
        }

        return redirect()->route('app.account.index', ['tab' => 'security'])
            ->with('error', __($status));
    }
}
