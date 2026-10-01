<?php

namespace App\Livewire\Owners;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Entry point of the owner area: the businesses this account owns and
 * the listings it submitted that are still being checked.
 */
#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): View
    {
        $userId = Auth::id();

        return view('livewire.owners.dashboard', [
            'owned' => Business::query()->where('owner_id', $userId)->with(['city', 'categories'])->orderBy('name')->get(),
            'submitted' => Business::query()->where('submitted_by', $userId)->whereNull('owner_id')->with('city')->latest()->limit(20)->get(),
        ])->title(__('Mon espace professionnel'))->layoutData(['noindex' => true]);
    }
}
