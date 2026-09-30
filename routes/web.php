<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ContactEventController;
use App\Http\Controllers\LocaleRedirectController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PharmacyDutyController;
use App\Http\Middleware\SetLocale;
use App\Livewire\Account\VerifyPhone;
use App\Livewire\Auth\EmailLogin;
use App\Livewire\Auth\PhoneLogin;
use App\Livewire\Directory\BusinessPage;
use App\Livewire\Directory\CityHome;
use App\Livewire\Directory\SearchPage;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

// "/" => "/{preferred-locale}/{default-city}"
Route::get('/', LocaleRedirectController::class)->name('root');

// Registered once in the Google console, hence no locale prefix.
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware(['guest', 'throttle:20,1'])
    ->name('auth.google.callback');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', Locales::supported())])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', [CityController::class, 'redirectToDefault'])->name('locale.home');

        Route::middleware('guest')->group(function () {
            Route::livewire('login', PhoneLogin::class)->name('login');
            Route::livewire('login/email', EmailLogin::class)->name('login.email');
            Route::get('login/google', [GoogleAuthController::class, 'redirect'])->name('login.google');
        });

        Route::middleware('auth')->group(function () {
            Route::post('logout', LogoutController::class)->name('logout');
            Route::livewire('account/phone', VerifyPhone::class)->name('account.phone');
        });

        Route::get('p/{page:slug}', [PageController::class, 'show'])->name('page.show');

        Route::post('events', ContactEventController::class)
            ->middleware('throttle:contact-events')
            ->name('contact-events.store');

        // Directory. Fixed segments ("b", "recherche", ...) are reserved slugs
        // (App\Support\ReservedSlugs) and are registered before the category catch-all.
        Route::prefix('{city:slug}')->group(function () {
            Route::livewire('/', CityHome::class)->name('city.home');
            Route::livewire('recherche', SearchPage::class)->name('city.search');
            Route::get('pharmacies-de-garde', PharmacyDutyController::class)->name('city.pharmacies');
            Route::livewire('b/{business:slug}', BusinessPage::class)->scopeBindings()->name('city.business');
            // Categories are shared by all cities: no scoping under {city}.
            Route::livewire('{category:slug}/{neighborhoodSlug?}', SearchPage::class)->withoutScopedBindings()->name('city.category');
        });
    });
