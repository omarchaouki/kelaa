<?php

namespace App\Livewire\Owners;

use App\Actions\Owners\ClaimBusiness;
use App\Enums\ClaimStatus;
use App\Exceptions\OtpDeliveryFailed;
use App\Exceptions\OtpThrottled;
use App\Exceptions\RateLimited;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\City;
use App\Support\Phone\MoroccanPhone;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ClaimPage extends Component
{
    use WithFileUploads;

    #[Locked]
    public City $city;

    #[Locked]
    public Business $business;

    #[Locked]
    public ?int $phoneClaimId = null;

    public string $code = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $documents = [];

    public string $notes = '';

    #[Locked]
    public ?string $result = null;

    public function mount(City $city, Business $business): void
    {
        abort_unless($city->is_active && $business->isPublished(), 404);

        if (Auth::user() === null || ! Auth::user()->hasVerifiedPhone()) {
            session()->put('url.intended', request()->fullUrl());
            $this->redirectRoute(Auth::user() === null ? 'login' : 'account.phone', navigate: true);

            return;
        }

        $this->city = $city;
        $this->business = $business;
    }

    public function sendCode(ClaimBusiness $claims): void
    {
        try {
            $this->phoneClaimId = $claims->startPhoneClaim(Auth::user(), $this->business, (string) request()->ip())->id;
        } catch (AuthorizationException $e) {
            $this->addError('code', $e->getMessage());
        } catch (RateLimited|OtpThrottled $e) {
            $this->addError('code', __('Trop de tentatives. Réessayez plus tard.'));
        } catch (OtpDeliveryFailed $e) {
            Log::error($e->getMessage());
            $this->addError('code', __("Impossible d'envoyer le code pour le moment. Réessayez plus tard."));
        } catch (InvalidArgumentException) {
            $this->addError('code', __('Cette fiche n’a pas de numéro mobile : utilisez les justificatifs.'));
        }
    }

    public function verifyCode(ClaimBusiness $claims): mixed
    {
        $this->validate(['code' => ['required', 'digits:'.config('otp.length')]]);

        $claim = BusinessClaim::query()->whereKey($this->phoneClaimId)->where('user_id', Auth::id())->first();

        if ($claim === null) {
            return null;
        }

        try {
            $valid = $claims->verifyPhoneClaim(Auth::user(), $claim, $this->code);
        } catch (OtpThrottled) {
            $this->addError('code', __('Trop de tentatives. Réessayez plus tard.'));

            return null;
        } catch (AuthorizationException) {
            return null;
        }

        if (! $valid) {
            $this->addError('code', __('Code incorrect ou expiré.'));

            return null;
        }

        return $this->redirectRoute('dashboard.business', ['business' => $this->business->id], navigate: true);
    }

    public function submitDocuments(ClaimBusiness $claims): void
    {
        $this->validate([
            'documents' => ['required', 'array', 'min:1', 'max:'.config('owners.claim_documents_max')],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.config('owners.claim_document_kb')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $claims->submitDocuments(Auth::user(), $this->business, array_values($this->documents), $this->notes ?: null);
        } catch (AuthorizationException $e) {
            $this->addError('documents', $e->getMessage());

            return;
        } catch (RateLimited) {
            $this->addError('documents', __('Trop de tentatives. Réessayez plus tard.'));

            return;
        } catch (InvalidArgumentException) {
            $this->addError('documents', __('Un des fichiers n’a pas pu être lu.'));

            return;
        }

        $this->reset('documents', 'notes');
        $this->result = 'documents_sent';
    }

    public function render(ClaimBusiness $claims): View
    {
        $phone = $claims->claimPhone($this->business);

        return view('livewire.owners.claim', [
            'maskedPhone' => $phone === null ? null : substr(MoroccanPhone::formatNational($phone), 0, 5).' •• •• '.substr($phone, -2),
            'pending' => BusinessClaim::query()
                ->where('business_id', $this->business->id)
                ->where('user_id', Auth::id())
                ->where('status', ClaimStatus::Pending)
                ->exists(),
            'alreadyOwned' => $this->business->owner_id !== null,
            'isOwner' => $this->business->owner_id === Auth::id(),
        ])->title(__('Revendiquer :business', ['business' => $this->business->name]))
            ->layoutData(['noindex' => true]);
    }
}
