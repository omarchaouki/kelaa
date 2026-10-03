<?php

namespace App\Actions\Owners;

use App\Actions\Reviews\SubmitReview;
use App\Enums\ClaimMethod;
use App\Enums\ClaimStatus;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpThrottled;
use App\Exceptions\RateLimited;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Support\Moderation\ModerationGate;
use App\Support\Phone\MoroccanPhone;
use App\Support\Security\SafeImage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * "Is this your business? Claim it."
 *
 * - By phone: a code is sent to the number published on the listing; typing
 *   it back proves control of that number, so the claim is approved at once.
 * - By documents (registre de commerce, patente, CIN): stored privately and
 *   reviewed by a moderator.
 */
class ClaimBusiness
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly ApproveClaim $approve,
    ) {}

    /**
     * Mobile number of the listing that can receive the code, if any.
     */
    public function claimPhone(Business $business): ?string
    {
        // Without an SMS provider, claims go through documents only.
        if (! config('auth_options.sms_verification')) {
            return null;
        }

        return $business->whatsapp ?? MoroccanPhone::normalize($business->phone);
    }

    /**
     * @throws AuthorizationException
     * @throws RateLimited
     * @throws OtpThrottled
     */
    public function startPhoneClaim(User $user, Business $business, string $ip): BusinessClaim
    {
        $this->assertCanClaim($user, $business);
        $phone = $this->claimPhone($business) ?? throw new InvalidArgumentException('This listing has no mobile number.');

        RateLimited::guard('claims:'.$user->id, (int) config('owners.claims_per_day'), 86400);

        $claim = $this->pendingClaim($user, $business, ClaimMethod::PhoneOtpToBusinessNumber);
        $this->otp->request($phone, OtpPurpose::BusinessClaim, $ip);

        return $claim;
    }

    /**
     * @throws AuthorizationException
     * @throws OtpThrottled
     */
    public function verifyPhoneClaim(User $user, BusinessClaim $claim, string $code): bool
    {
        if ($claim->user_id !== $user->id || $claim->status !== ClaimStatus::Pending || $claim->method !== ClaimMethod::PhoneOtpToBusinessNumber) {
            throw new AuthorizationException;
        }

        $claim->loadMissing('business');
        $phone = $this->claimPhone($claim->business);

        if ($phone === null || ! $this->otp->verify($phone, OtpPurpose::BusinessClaim, $code)) {
            return false;
        }

        $claim->forceFill(['phone_verified_at' => now()])->save();

        ModerationGate::asSystem(fn () => ($this->approve)($claim, null));

        return true;
    }

    /**
     * @param  list<UploadedFile>  $documents
     *
     * @throws AuthorizationException
     * @throws RateLimited
     */
    public function submitDocuments(User $user, Business $business, array $documents, ?string $notes): BusinessClaim
    {
        $this->assertCanClaim($user, $business);

        if ($documents === [] || count($documents) > (int) config('owners.claim_documents_max')) {
            throw new InvalidArgumentException('Invalid number of documents.');
        }

        RateLimited::guard('claims:'.$user->id, (int) config('owners.claims_per_day'), 86400);

        return DB::transaction(function () use ($user, $business, $documents, $notes): BusinessClaim {
            $claim = $this->pendingClaim($user, $business, ClaimMethod::DocumentUpload);
            $claim->fill(['notes' => $notes === null ? null : mb_substr((string) SubmitReview::cleanBody($notes), 0, 1000)])->save();

            foreach ($documents as $document) {
                $claim->addMedia($this->sanitize($document))
                    ->usingFileName(bin2hex(random_bytes(16)).'.'.($this->isPdf($document) ? 'pdf' : 'jpg'))
                    ->toMediaCollection('documents');
            }

            return $claim;
        });
    }

    /**
     * @throws AuthorizationException
     */
    private function assertCanClaim(User $user, Business $business): void
    {
        if ($user->isBanned() || ! $user->hasVerifiedPhone()) {
            throw new AuthorizationException(__('Un numéro de mobile vérifié est nécessaire.'));
        }

        if (! $business->isPublished() || $business->is_claimed || $business->owner_id !== null) {
            throw new AuthorizationException(__('Cette fiche a déjà un propriétaire.'));
        }
    }

    private function pendingClaim(User $user, Business $business, ClaimMethod $method): BusinessClaim
    {
        $claim = BusinessClaim::query()
            ->where('business_id', $business->id)
            ->where('user_id', $user->id)
            ->where('status', ClaimStatus::Pending)
            ->where('method', $method)
            ->first();

        if ($claim !== null) {
            return $claim;
        }

        $claim = new BusinessClaim(['business_id' => $business->id, 'user_id' => $user->id, 'method' => $method]);
        $claim->forceFill(['status' => ClaimStatus::Pending])->save();

        return $claim;
    }

    /**
     * Images are re-encoded (EXIF and hidden payloads removed); PDFs must
     * really be PDFs. Anything else is refused.
     */
    private function sanitize(UploadedFile $file): string
    {
        if ($this->isPdf($file)) {
            return (string) $file->getRealPath();
        }

        return SafeImage::reencode($file);
    }

    private function isPdf(UploadedFile $file): bool
    {
        $handle = fopen((string) $file->getRealPath(), 'rb');
        $magic = $handle === false ? '' : (string) fread($handle, 5);

        if ($handle !== false) {
            fclose($handle);
        }

        return $magic === '%PDF-';
    }
}
