<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CmiCallbackController;
use App\Http\Controllers\ContactEventController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocaleRedirectController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PharmacyDutyController;
use App\Http\Controllers\PrivateMediaController;
use App\Http\Controllers\RobotsController;
use App\Http\Middleware\SetLocale;
use App\Livewire\Account\PrivacyPage;
use App\Livewire\Account\VerifyPhone;
use App\Livewire\Auth\EmailLogin;
use App\Livewire\Auth\PasswordLogin;
use App\Livewire\Auth\PhoneLogin;
use App\Livewire\Directory\BusinessPage;
use App\Livewire\Directory\CityHome;
use App\Livewire\Directory\SearchPage;
use App\Livewire\Owners\AddBusiness;
use App\Livewire\Owners\BillingPage;
use App\Livewire\Owners\BusinessDashboard;
use App\Livewire\Owners\ClaimPage;
use App\Livewire\Owners\Dashboard;
use App\Livewire\Owners\SuggestEditPage;
use App\Support\Locales;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

// "/" => "/{preferred-locale}/{default-city}"
Route::get('/', LocaleRedirectController::class)->name('root');

// Crawlers, PWA and uptime probes.
Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('manifest.webmanifest', ManifestController::class)->name('manifest');
Route::get('health', HealthController::class)->middleware('throttle:30,1')->name('health');

// Private files (claim documents) for moderators only.
Route::get('admin-files/media/{media}', PrivateMediaController::class)
    ->middleware(['auth', 'throttle:60,1'])
    ->name('admin.private-media');

Route::get('admin-files/invoices/{invoice}', [InvoiceController::class, 'admin'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('admin.invoice');

// CMI card gateway (inactive until the integration is finished: 404).
Route::post('payments/cmi/callback', [CmiCallbackController::class, 'callback'])
    ->withoutMiddleware([PreventRequestForgery::class])
    ->middleware('throttle:60,1')
    ->name('payments.cmi.callback');
Route::match(['get', 'post'], 'payments/cmi/ok', [CmiCallbackController::class, 'return'])->name('payments.cmi.ok');
Route::match(['get', 'post'], 'payments/cmi/fail', [CmiCallbackController::class, 'return'])->name('payments.cmi.fail');

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
            // Phone + SMS code when an SMS provider is configured, phone + password otherwise.
            Route::livewire('login', config('auth_options.sms_verification') ? PhoneLogin::class : PasswordLogin::class)->name('login');
            Route::livewire('login/email', EmailLogin::class)->name('login.email');
            Route::get('login/google', [GoogleAuthController::class, 'redirect'])->name('login.google');
        });

        Route::middleware('auth')->group(function () {
            Route::post('logout', LogoutController::class)->name('logout');
            Route::livewire('account/phone', VerifyPhone::class)->name('account.phone');
            Route::livewire('account/donnees', PrivacyPage::class)->name('account.privacy');
            Route::get('account/donnees/export/{token}', DataExportController::class)
                ->middleware(['signed', 'throttle:10,1'])
                ->name('account.export.download');

            // Owner area. Business ids are re-authorized by BusinessPolicy::manage on every request.
            Route::livewire('dashboard', Dashboard::class)->name('dashboard');
            Route::livewire('dashboard/{business:id}', BusinessDashboard::class)->name('dashboard.business');
            Route::livewire('dashboard/{business:id}/facturation', BillingPage::class)->name('dashboard.billing');
            Route::get('dashboard/{business:id}/factures/{invoice}', [InvoiceController::class, 'owner'])
                ->middleware('throttle:30,1')
                ->name('dashboard.invoice');
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
            Route::livewire('ajouter', AddBusiness::class)->middleware('auth')->name('city.add');
            Route::get('pharmacies-de-garde', PharmacyDutyController::class)->name('city.pharmacies');
            Route::livewire('b/{business:slug}', BusinessPage::class)->scopeBindings()->name('city.business');
            Route::livewire('b/{business:slug}/modifier', SuggestEditPage::class)->middleware('auth')->scopeBindings()->name('city.business.suggest');
            Route::livewire('b/{business:slug}/revendiquer', ClaimPage::class)->middleware('auth')->scopeBindings()->name('city.business.claim');
            // Categories are shared by all cities: no scoping under {city}.
            Route::livewire('{category:slug}/{neighborhoodSlug?}', SearchPage::class)->withoutScopedBindings()->name('city.category');
        });
    });
