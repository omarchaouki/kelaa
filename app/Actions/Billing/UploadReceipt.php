<?php

namespace App\Actions\Billing;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Support\Security\SafeImage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * The owner attaches proof of a bank transfer or cash deposit. Stored on the
 * private disk; images are re-encoded, PDFs must really be PDFs.
 */
class UploadReceipt
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $owner, Payment $payment, UploadedFile $file): void
    {
        $payment->loadMissing('business');

        if ($payment->user_id !== $owner->id || ! $owner->can('manage', $payment->business) || $payment->status !== PaymentStatus::Pending) {
            throw new AuthorizationException;
        }

        $path = $this->isPdf($file) ? (string) $file->getRealPath() : SafeImage::reencode($file);

        $payment->addMedia($path)
            ->usingFileName(bin2hex(random_bytes(16)).($this->isPdf($file) ? '.pdf' : '.jpg'))
            ->toMediaCollection('receipt');

        // A receipt keeps the order (and its sponsored slot) alive while an admin checks it.
        $payment->forceFill(['expires_at' => now()->addDays(7)->max($payment->expires_at ?? now())])->save();

        activity('billing')->performedOn($payment)->causedBy($owner)->log('payment.receipt_uploaded');
    }

    private function isPdf(UploadedFile $file): bool
    {
        $handle = fopen((string) $file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('Unreadable file.');
        }

        $magic = (string) fread($handle, 5);
        fclose($handle);

        return $magic === '%PDF-';
    }
}
