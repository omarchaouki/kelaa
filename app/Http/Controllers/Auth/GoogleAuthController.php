<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResolveUserForGoogleAccount;
use App\Http\Controllers\Controller;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

/**
 * Optional Google login (AUTH_GOOGLE_ENABLED). The callback URL has no
 * locale prefix because it is registered once in the Google console.
 */
class GoogleAuthController extends Controller
{
    public function redirect(Request $request): SymfonyRedirect
    {
        abort_unless(config('auth_options.google'), 404);

        $request->session()->put('auth.google.locale', app()->getLocale());

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, ResolveUserForGoogleAccount $resolveUser): RedirectResponse
    {
        abort_unless(config('auth_options.google'), 404);

        $locale = $request->session()->pull('auth.google.locale');
        $locale = Locales::isSupported($locale) ? $locale : Locales::default();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login', ['locale' => $locale]);
        }

        $user = $resolveUser($googleUser, $locale);

        if ($user->isBanned()) {
            return redirect()->route('login', ['locale' => $locale])->withErrors(['phone' => __('Ce compte est suspendu.')]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Reviews require a verified phone: ask for it right away.
        if (! $user->hasVerifiedPhone()) {
            return redirect()->route('account.phone', ['locale' => $locale]);
        }

        return redirect()->intended(route('locale.home', ['locale' => $locale]));
    }
}
