<?php

namespace App\Http\Controllers;

use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Models\Business;
use App\Models\SponsoredPlacement;
use App\Services\Tracking\ContactEventRecorder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Receives contact button clicks sent with navigator.sendBeacon().
 * CSRF-protected (token in the payload), throttled per IP, and it always
 * answers 204 so the endpoint reveals nothing to scrapers.
 */
class ContactEventController extends Controller
{
    public function __invoke(Request $request, ContactEventRecorder $recorder): Response
    {
        $data = $request->validate([
            'business' => ['required', 'integer', 'min:1'],
            // Profile views are recorded server-side when the page renders.
            'type' => ['required', Rule::enum(ContactEventType::class)->except([ContactEventType::ProfileView])],
            'source' => ['nullable', Rule::enum(ContactEventSource::class)],
            'placement' => ['nullable', 'integer', 'min:1'],
        ]);

        $business = Business::query()->published()->find($data['business']);

        if ($business !== null) {
            $placement = isset($data['placement']) ? SponsoredPlacement::query()->find($data['placement']) : null;

            $recorder->record(
                $business,
                ContactEventType::from($data['type']),
                ContactEventSource::tryFrom($data['source'] ?? '') ?? ContactEventSource::Organic,
                $request,
                $placement,
            );
        }

        return response()->noContent();
    }
}
