<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Models\User;
use App\Rules\MoroccanPhoneNumber;
use App\Support\Phone\MoroccanPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Phone number + password, used while SMS verification is off
 * (SMS_VERIFICATION_ENABLED=false). One page, two tabs: log in / sign up.
 */
#[Layout('layouts.app')]
class PasswordLogin extends Component
{
    #[Url(as: 'mode', except: 'login')]
    public string $mode = 'login';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        abort_if(config('auth_options.sms_verification'), 404);

        if (! in_array($this->mode, ['login', 'register'], true)) {
            $this->mode = 'login';
        }
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode === 'register' ? 'register' : 'login';
        $this->reset('password', 'password_confirmation');
        $this->resetErrorBag();
    }

    public function login(): mixed
    {
        $this->validate([
            'phone' => ['required', 'string', 'max:20', MoroccanPhoneNumber::mobile()],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $phone = (string) MoroccanPhone::normalize($this->phone);

        if (! $this->throttle($phone)) {
            return null;
        }

        $user = User::query()->where('phone', $phone)->first();

        // Same answer (and similar timing) whether the number exists or the password is wrong.
        $valid = Hash::check($this->password, $user->password ?? self::dummyHash());

        if ($user === null || $user->password === null || ! $valid) {
            $this->hit($phone);
            $this->addError('phone', __('Numéro ou mot de passe incorrect.'));
            $this->reset('password');

            return null;
        }

        if ($user->isBanned()) {
            $this->addError('phone', __('Ce compte est suspendu.'));

            return null;
        }

        $this->clear($phone);

        return $this->signIn($user);
    }

    public function register(): mixed
    {
        $this->validate([
            'phone' => ['required', 'string', 'max:20', MoroccanPhoneNumber::mobile()],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $phone = (string) MoroccanPhone::normalize($this->phone);
        $ipKey = 'register:ip:'.request()->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $this->addError('phone', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil(RateLimiter::availableIn($ipKey) / 60)]));

            return null;
        }

        RateLimiter::hit($ipKey, 3600);

        $user = DB::transaction(function () use ($phone): ?User {
            if (User::query()->where('phone', $phone)->lockForUpdate()->exists()) {
                return null;
            }

            $user = User::query()->create([
                'phone' => $phone,
                'password' => $this->password,
                'locale' => app()->getLocale(),
            ]);
            $user->assignRole(Role::Member->value);

            return $user;
        });

        if ($user === null) {
            $this->addError('phone', __('Ce numéro a déjà un compte. Connectez-vous.'));

            return null;
        }

        return $this->signIn($user);
    }

    public function render(): View
    {
        return view('livewire.auth.password-login')
            ->title($this->mode === 'register' ? __('Créer un compte') : __('Connexion'));
    }

    private function signIn(User $user): mixed
    {
        Auth::login($user, remember: true);
        session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return $this->redirectIntended(route('locale.home'), navigate: true);
    }

    /**
     * Brute-force guard: per number (any IP) and per IP (any number).
     */
    private function throttle(string $phone): bool
    {
        foreach ([$this->phoneKey($phone), $this->ipKey()] as $key) {
            if (RateLimiter::tooManyAttempts($key, $key === $this->ipKey() ? 20 : 5)) {
                $this->addError('phone', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)]));

                return false;
            }
        }

        return true;
    }

    private function hit(string $phone): void
    {
        RateLimiter::hit($this->phoneKey($phone), 900);
        RateLimiter::hit($this->ipKey(), 900);
    }

    private function clear(string $phone): void
    {
        RateLimiter::clear($this->phoneKey($phone));
    }

    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make('not-a-real-password');
    }

    private function phoneKey(string $phone): string
    {
        return 'login:phone:'.$phone;
    }

    private function ipKey(): string
    {
        return 'login:ip:'.request()->ip();
    }
}
