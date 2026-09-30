<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Optional email + password login (AUTH_EMAIL_PASSWORD_ENABLED) for accounts
 * that have set a password. Sign-up always goes through the phone.
 */
#[Layout('layouts.app')]
class EmailLogin extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = true;

    public function mount(): void
    {
        abort_unless(config('auth_options.email_password'), 404);
    }

    public function login(): mixed
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:email:'.Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]));

            return null;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password, 'banned_at' => null], $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->addError('email', __('auth.failed'));

            return null;
        }

        RateLimiter::clear($key);
        session()->regenerate();
        Auth::user()?->forceFill(['last_login_at' => now()])->save();

        return $this->redirectIntended(route('locale.home'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.email-login')->title(__('Connexion par e-mail'));
    }
}
