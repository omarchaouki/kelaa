<?php

namespace App\Http\Controllers;

use App\Jobs\ExportUserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    /**
     * Signed URL + logged in as the same user: a leaked link alone is useless.
     */
    public function __invoke(Request $request, string $token): StreamedResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{40}$/', $token) === 1, 404);

        $path = ExportUserData::path((int) $request->user()?->id, $token);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, 'mes-donnees-'.now()->format('Y-m-d').'.json', [
            'Content-Type' => 'application/json',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
