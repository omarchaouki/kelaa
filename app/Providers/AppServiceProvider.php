<?php

namespace App\Providers;

use App\Contracts\OtpSender;
use App\Http\Middleware\SetLocale;
use App\Models;
use App\Services\Otp\LogOtpSender;
use App\Services\Otp\SmsOtpSender;
use App\Services\Otp\WhatsAppOtpSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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

        // App strings live apart from the Laravel Lang package files (lang/{locale}.json).
        // Added as soon as the translator exists: a provider translating anything while
        // booting would otherwise cache the default locale's strings without this file.
        $this->callAfterResolving('translator', fn ($translator) => $translator->addJsonPath(lang_path('app')));
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
            'placement_rate' => Models\PlacementRate::class,
            'payment' => Models\Payment::class,
            'invoice' => Models\Invoice::class,
            'pharmacy_duty' => Models\PharmacyDuty::class,
            'page' => Models\Page::class,
            'search_synonym' => Models\SearchSynonym::class,
            'banned_word' => Models\BannedWord::class,
            'media' => Media::class,
        ]);

        // route() works outside a localized request too (jobs, mails, console).
        // SetLocale overrides this default for every localized request.
        URL::defaults(['locale' => config('app.locale')]);

        // Livewire requests hit /livewire/update: re-apply the page's locale.
        Livewire::addPersistentMiddleware([SetLocale::class]);

        $this->configureSecurity();
        $this->registerSqliteMathFunctions();
    }

    private function configureSecurity(): void
    {
        // Behind a TLS-terminating proxy, every generated URL must stay HTTPS.
        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        if ($proxies = config('security.trusted_proxies')) {
            TrustProxies::at($proxies);
        }

        RateLimiter::for('livewire', fn (Request $request) => Limit::perMinute((int) config('security.rate_limits.livewire'))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('contact-events', fn (Request $request) => Limit::perMinute((int) config('security.rate_limits.contact_events'))
            ->by($request->ip()));

        // Every Livewire round trip (search-as-you-type, filters) is throttled.
        Livewire::setUpdateRoute(fn ($handle, string $path) => Route::post($path, $handle)
            ->middleware(['web', 'throttle:livewire']));
    }

    /**
     * SQLite (used for quick local test runs) ships without trigonometric
     * functions; the haversine fallback in GeoQuery needs them.
     */
    private function registerSqliteMathFunctions(): void
    {
        // Connections opened (or re-opened) from now on...
        Event::listen(fn (ConnectionEstablished $event) => self::addSqliteMathFunctions($event->connection));

        // ...and those already opened while providers booted: their
        // ConnectionEstablished event fired before this listener existed.
        foreach (DB::getConnections() as $connection) {
            self::addSqliteMathFunctions($connection);
        }
    }

    private static function addSqliteMathFunctions(Connection $connection): void
    {
        if (! $connection instanceof SQLiteConnection) {
            return;
        }

        $pdo = $connection->getPdo();

        foreach (['sin', 'cos', 'asin', 'sqrt'] as $function) {
            $pdo->sqliteCreateFunction($function, $function, 1);
        }

        $pdo->sqliteCreateFunction('radians', 'deg2rad', 1);
        $pdo->sqliteCreateFunction('power', 'pow', 2);
    }
}
