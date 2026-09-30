<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Http\Middleware\SetLocale;
use App\Models;
use App\Services\Otp\LogOtpSender;
use App\Services\Otp\SmsOtpSender;
use App\Services\Otp\WhatsAppOtpSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpSender::class, fn () => match (config('otp.driver')) {
            'log' => new LogOtpSender,
            'sms' => new SmsOtpSender(config('otp.drivers.sms')),
            'whatsapp' => new WhatsAppOtpSender(config('otp.drivers.whatsapp')),
            default => throw new InvalidArgumentException('Unknown OTP driver ['.config('otp.driver').'].'),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Short, stable names in polymorphic columns (reports, media, roles, activity log).
        Relation::enforceMorphMap([
            'user' => Models\User::class,
            'city' => Models\City::class,
            'neighborhood' => Models\Neighborhood::class,
            'category' => Models\Category::class,
            'business' => Models\Business::class,
            'business_hour' => Models\BusinessHour::class,
            'business_special_hour' => Models\BusinessSpecialHour::class,
            'review' => Models\Review::class,
            'review_reply' => Models\ReviewReply::class,
            'business_claim' => Models\BusinessClaim::class,
            'edit_suggestion' => Models\EditSuggestion::class,
            'report' => Models\Report::class,
            'plan' => Models\Plan::class,
            'subscription' => Models\Subscription::class,
            'sponsored_placement' => Models\SponsoredPlacement::class,
            'payment' => Models\Payment::class,
            'invoice' => Models\Invoice::class,
            'pharmacy_duty' => Models\PharmacyDuty::class,
            'page' => Models\Page::class,
            'search_synonym' => Models\SearchSynonym::class,
            'banned_word' => Models\BannedWord::class,
            'media' => Media::class,
        ]);

        // App strings live apart from the Laravel Lang package files (lang/{locale}.json).
        $this->app['translator']->addJsonPath(lang_path('app'));

        // Livewire requests hit /livewire/update: re-apply the page's locale.
        Livewire::addPersistentMiddleware([SetLocale::class]);

        $this->registerSqliteMathFunctions();
    }

    /**
     * SQLite (used for quick local test runs) ships without trigonometric
     * functions; the haversine fallback in GeoQuery needs them.
     */
    private function registerSqliteMathFunctions(): void
    {
        Event::listen(function (ConnectionEstablished $event): void {
            if (! $event->connection instanceof SQLiteConnection) {
                return;
            }

            $pdo = $event->connection->getPdo();

            foreach (['sin', 'cos', 'asin', 'sqrt'] as $function) {
                $pdo->sqliteCreateFunction($function, $function, 1);
            }

            $pdo->sqliteCreateFunction('radians', 'deg2rad', 1);
            $pdo->sqliteCreateFunction('power', 'pow', 2);
        });
    }
}
