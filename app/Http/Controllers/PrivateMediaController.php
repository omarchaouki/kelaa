<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\BusinessClaim;
use App\Models\Payment;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves private files (claim documents, payment receipts) to staff only.
 * Files are sandboxed: they can never run scripts on our origin, PDFs are
 * downloaded instead of rendered, and nothing is cached.
 */
class PrivateMediaController extends Controller
{
    public function __invoke(Request $request, Media $media): BinaryFileResponse
    {
        $user = $request->user();

        $allowed = match (true) {
            $media->model_type === (new BusinessClaim)->getMorphClass() && $media->collection_name === 'documents' => $user?->can(Permission::ModerateClaims->value),
            $media->model_type === (new Payment)->getMorphClass() && $media->collection_name === 'receipt' => $user?->can(Permission::ManagePayments->value),
            default => false,
        };

        abort_unless($allowed === true && ! $user?->isBanned(), 404);

        activity('private-files')->causedBy($user)->withProperties(['media_id' => $media->id])->log('private_media.viewed');

        $isImage = str_starts_with((string) $media->mime_type, 'image/');

        return response()->file($media->getPath(), [
            'Content-Type' => $isImage ? 'image/jpeg' : 'application/pdf',
            'Content-Disposition' => ($isImage ? 'inline' : 'attachment').'; filename="document-'.$media->id.($isImage ? '.jpg' : '.pdf').'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
