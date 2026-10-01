<?php

namespace App\Livewire\Owners;

use App\Actions\Owners\SuggestEdit;
use App\Exceptions\RateLimited;
use App\Models\Business;
use App\Models\City;
use App\Support\Phone\MoroccanPhone;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class SuggestEditPage extends Component
{
    #[Locked]
    public City $city;

    #[Locked]
    public Business $business;

    public string $name = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $website = '';

    public string $address = '';

    public bool $permanentlyClosed = false;

    public string $comment = '';

    public bool $sent = false;

    public function mount(City $city, Business $business): void
    {
        abort_unless($city->is_active && $business->isPublished(), 404);

        $user = Auth::user();

        if ($user === null || ! $user->hasVerifiedPhone()) {
            session()->put('url.intended', request()->fullUrl());
            $this->redirectRoute($user === null ? 'login' : 'account.phone', navigate: true);

            return;
        }

        $this->city = $city;
        $this->business = $business;
        $this->name = $business->name;
        $this->phone = $business->phone ? MoroccanPhone::formatNational($business->phone) : '';
        $this->whatsapp = $business->whatsapp ? MoroccanPhone::formatNational($business->whatsapp) : '';
        $this->website = (string) $business->website;
        $this->address = (string) $business->address;
    }

    public function submit(SuggestEdit $suggest): void
    {
        $this->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        try {
            $suggestion = $suggest(Auth::user(), $this->business, [
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'whatsapp' => $this->whatsapp ?: null,
                'website' => $this->website ?: null,
                'address' => $this->address ?: null,
                'permanently_closed' => $this->permanentlyClosed,
            ], $this->comment ?: null);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        } catch (RateLimited $e) {
            $this->addError('comment', __('Trop de tentatives. Réessayez dans :minutes min.', ['minutes' => $e->minutes()]));

            return;
        } catch (AuthorizationException $e) {
            $this->addError('comment', $e->getMessage());

            return;
        }

        if ($suggestion === null) {
            $this->addError('comment', __('Aucune modification détectée.'));

            return;
        }

        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.owners.suggest-edit', [
            'isOwner' => Auth::id() === $this->business->owner_id,
        ])->title(__('Proposer une modification · :business', ['business' => $this->business->name]))
            ->layoutData(['noindex' => true]);
    }
}
