<?php

namespace App\Livewire\Account;

use App\Actions\Account\DeleteAccount;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryFailed;
use App\Exceptions\OtpThrottled;
use App\Exceptions\RateLimited;
use App\Jobs\ExportUserData;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Support\Phone\MoroccanPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "Mes données": download a copy of one's data, delete one's account.
 * Deletion is confirmed by a code sent to the verified phone, or, for
 * accounts without a phone, by a sign-in less than 15 minutes old.
 */
#[Layout('layouts.app')]
class PrivacyPage extends Component
{
    public const RECENT_LOGIN_MINUTES = 15;

    public bool $removeReviews = false;

    public bool $understood = false;

    public string $code = '';

    #[Locked]
    public bool $codeSent = false;

    public ?string $flash = null;

    protected function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function requestExport(): void
    {
        try {
            RateLimited::guard('data-export:'.$this->user()->id, 1, 86400);
        } catch (RateLimited) {
            $this->addError('export', __('Une copie a déjà été demandée aujourd’hui. Réessayez demain.'));

            return;
        }

        ExportUserData::dispatch($this->user()->id);
        $this->flash = __('Demande enregistrée. Vous recevrez un lien de téléchargement dans vos notifications et par e-mail.');
    }

    public function startDeletion(OtpService $otp): void
    {
        $this->validate(['understood' => ['accepted']], attributes: ['understood' => __('confirmation')]);

        $user = $this->user();

        if ($user->isStaff()) {
            $this->addError('understood', __('Les comptes de l’équipe sont fermés par un administrateur.'));

            return;
        }

        if (! $user->hasVerifiedPhone()) {
            return;  // confirmed by a recent sign-in instead, see confirmDeletion()
        }

        try {
            $otp->request((string) $user->phone, OtpPurpose::AccountDeletion, (string) request()->ip());
        } catch (OtpThrottled $e) {
            $this->addError('understood', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil($e->retryAfterSeconds / 60)]));

            return;
        } catch (OtpDeliveryFailed $e) {
            Log::error($e->getMessage());
            $this->addError('understood', __("Impossible d'envoyer le code pour le moment. Réessayez plus tard."));

            return;
        }

        $this->codeSent = true;
    }

    public function confirmDeletion(OtpService $otp, DeleteAccount $delete): mixed
    {
        $this->validate(['understood' => ['accepted']], attributes: ['understood' => __('confirmation')]);
        $user = $this->user();

        if ($user->hasVerifiedPhone()) {
            if (! $this->codeSent) {
                return null;
            }

            $this->validate(['code' => ['required', 'digits:'.config('otp.length')]]);

            try {
                $valid = $otp->verify((string) $user->phone, OtpPurpose::AccountDeletion, $this->code);
            } catch (OtpThrottled $e) {
                $this->addError('code', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => (int) ceil($e->retryAfterSeconds / 60)]));

                return null;
            }

            if (! $valid) {
                $this->addError('code', __('Code incorrect ou expiré.'));

                return null;
            }
        } elseif ($user->last_login_at === null || $user->last_login_at->lt(now()->subMinutes(self::RECENT_LOGIN_MINUTES))) {
            $this->addError('understood', __('Pour votre sécurité, déconnectez-vous puis reconnectez-vous avant de supprimer votre compte.'));

            return null;
        }

        // Log out BEFORE deleting: logout() rotates the remember token and saves
        // the user, which would re-insert a row that was already deleted.
        Auth::guard('web')->logout();
        $delete($user, $this->removeReviews);

        session()->invalidate();
        session()->regenerateToken();
        session()->flash('status', __('Votre compte a été supprimé.'));

        return $this->redirectRoute('locale.home');
    }

    public function render(): View
    {
        $user = $this->user();

        return view('livewire.account.privacy', [
            'user' => $user,
            'displayPhone' => $user->phone ? MoroccanPhone::formatNational($user->phone) : null,
            'reviewCount' => $user->reviews()->count(),
            'ownsBusinesses' => $user->ownedBusinesses()->exists(),
        ])->title(__('Mes données'))->layoutData(['noindex' => true]);
    }
}
