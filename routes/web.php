<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\LocaleRedirectController;
use App\Http\Controllers\PageController;
use App\Http\Middleware\SetLocale;
use App\Livewire\Auth\PhoneLogin;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

// "/" => "/{preferred-locale}/{default-city}"
Route::get('/', LocaleRedirectController::class)->name('root');

Route::prefix('{locale}')
    ->where(['locale' => implode('|', Locales::supported())])
    ->middleware(SetLocale::class)
    ->group(function () {
        Route::get('/', [CityController::class, 'redirectToDefault'])->name('locale.home');

        Route::middleware('guest')->group(function () {
            Route::livewire('login', PhoneLogin::class)->name('login');
        });

        Route::post('logout', LogoutController::class)->middleware('auth')->name('logout');

        Route::get('p/{page:slug}', [PageController::class, 'show'])->name('page.show');

        // Keep last: catches "/{locale}/{city}".
        Route::get('{city:slug}', [CityController::class, 'show'])->name('city.home');
    });
