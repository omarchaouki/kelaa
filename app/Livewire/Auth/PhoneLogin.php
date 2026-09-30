<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\ResolveUserForVerifiedPhone;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryFailed;
use App\Exceptions\OtpThrottled;
use App\Rules\MoroccanPhoneNumber;
use App\Services\Otp\OtpService;
use App\Support\Phone\MoroccanPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Two steps: enter a Moroccan mobile number, then the code received.
 * The first successful login creates the account.
 */
#[Layout('layouts.app')]
class PhoneLogin extends Component
{
    public string $phone = '';

    public string $code = '';

    #[Locked]
    public ?string $normalizedPhone = null;

    public function sendCode(OtpService $otp): void
    {
        $this->validate(['phone' => ['required', 'string', MoroccanPhoneNumber::mobile()]]);

        $phone = MoroccanPhone::normalize($this->phone);

        try {
            $otp->request($phone, OtpPurpose::Login, (string) request()->ip());
        } catch (OtpThrottled $e) {
            $this->addError('phone', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil($e->retryAfterSeconds / 60)]));

            return;
        } catch (OtpDeliveryFailed $e) {
            Log::error($e->getMessage());
            $this->addError('phone', __("Impossible d'envoyer le code pour le moment. Réessayez plus tard."));

            return;
        }

        $this->normalizedPhone = $phone;
        $this->code = '';
    }

    public function verify(OtpService $otp, ResolveUserForVerifiedPhone $resolveUser): mixed
    {
        if ($this->normalizedPhone === null) {
            return null;
        }

        $this->validate(['code' => ['required', 'digits:'.config('otp.length')]]);

        try {
            $valid = $otp->verify($this->normalizedPhone, OtpPurpose::Login, $this->code);
        } catch (OtpThrottled $e) {
            $this->addError('code', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil($e->retryAfterSeconds / 60)]));

            return null;
        }

        if (! $valid) {
            $this->addError('code', __('Code incorrect ou expiré.'));

            return null;
        }

        $user = $resolveUser($this->normalizedPhone, app()->getLocale());

        if ($user->isBanned()) {
            $this->addError('code', __('Ce compte est suspendu.'));

            return null;
        }

        Auth::login($user, remember: true);
        session()->regenerate();

        return $this->redirectIntended(route('locale.home'), navigate: true);
    }

    public function changeNumber(): void
    {
        $this->reset('normalizedPhone', 'code');
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.auth.phone-login', [
            'displayPhone' => $this->normalizedPhone ? MoroccanPhone::formatNational($this->normalizedPhone) : null,
        ])->title(__('Connexion'));
    }
}
