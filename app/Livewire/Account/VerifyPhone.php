<?php

namespace App\Livewire\Account;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryFailed;
use App\Exceptions\OtpThrottled;
use App\Models\User;
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
 * Adds or changes the verified phone of a logged-in account
 * (e.g. after a Google sign-up). Required before publishing a review.
 */
#[Layout('layouts.app')]
class VerifyPhone extends Component
{
    public string $phone = '';

    public string $code = '';

    #[Locked]
    public ?string $normalizedPhone = null;

    public function sendCode(OtpService $otp): mixed
    {
        $this->validate(['phone' => ['required', 'string', MoroccanPhoneNumber::mobile()]]);

        $phone = MoroccanPhone::normalize($this->phone);

        if (User::query()->where('phone', $phone)->whereKeyNot(Auth::id())->exists()) {
            $this->addError('phone', __('Ce numéro est déjà associé à un autre compte.'));

            return null;
        }

        // No SMS provider yet: the number is saved as given, unverified.
        if (! config('auth_options.sms_verification')) {
            /** @var User $user */
            $user = Auth::user();
            $user->forceFill(['phone' => $phone, 'phone_verified_at' => null])->save();
            session()->flash('status', __('Votre numéro est enregistré.'));

            return $this->redirectIntended(route('locale.home'), navigate: true);
        }

        try {
            $otp->request($phone, OtpPurpose::PhoneVerification, (string) request()->ip());
        } catch (OtpThrottled $e) {
            $this->addError('phone', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil($e->retryAfterSeconds / 60)]));

            return null;
        } catch (OtpDeliveryFailed $e) {
            Log::error($e->getMessage());
            $this->addError('phone', __("Impossible d'envoyer le code pour le moment. Réessayez plus tard."));

            return null;
        }

        $this->normalizedPhone = $phone;

        return null;
    }

    public function verify(OtpService $otp): mixed
    {
        if ($this->normalizedPhone === null) {
            return null;
        }

        $this->validate(['code' => ['required', 'digits:'.config('otp.length')]]);

        try {
            $valid = $otp->verify($this->normalizedPhone, OtpPurpose::PhoneVerification, $this->code);
        } catch (OtpThrottled $e) {
            $this->addError('code', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil($e->retryAfterSeconds / 60)]));

            return null;
        }

        if (! $valid) {
            $this->addError('code', __('Code incorrect ou expiré.'));

            return null;
        }

        /** @var User $user */
        $user = Auth::user();
        $user->forceFill(['phone' => $this->normalizedPhone, 'phone_verified_at' => now()])->save();

        session()->flash('status', __('Votre numéro est vérifié.'));

        return $this->redirectIntended(route('locale.home'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.account.verify-phone', [
            'currentPhone' => Auth::user()?->phone ? MoroccanPhone::formatNational(Auth::user()->phone) : null,
            'displayPhone' => $this->normalizedPhone ? MoroccanPhone::formatNational($this->normalizedPhone) : null,
        ])->title(__('Vérifier mon numéro'));
    }
}
