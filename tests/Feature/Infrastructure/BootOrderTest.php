<?php

use App\Models\Business;
use App\Models\City;
use App\Models\Review;
use App\Services\Ranking\RankingService;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

/**
 * Regressions found on the dev server, invisible to the regular test setup:
 * in a real request, providers may open the database connection or
 * translate a string while the application boots, before
 * AppServiceProvider runs.
 */

/**
 * Boots a separate application; $early runs before any provider boots.
 *
 * @param  callable(Application): void  $early
 * @param  callable(Application): mixed  $check
 */
function bootSeparately(callable $early, callable $check): mixed
{
    $testApp = Container::getInstance();

    try {
        /** @var Application $app */
        $app = require base_path('bootstrap/app.php');
        $app->booting($early);
        $app->make(Kernel::class)->bootstrap();

        return $check($app);
    } finally {
        Container::setInstance($testApp);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($testApp);
    }
}

it('adds the SQLite math functions to a connection opened while booting', function () {
    $value = bootSeparately(
        fn (Application $app) => $app['db']->connection()->getPdo(),
        fn (Application $app) => $app['db']->connection()->selectOne('select sqrt(16) as a, power(2, 3) as b, radians(180) as c, asin(1) as d'),
    );

    expect((float) $value->a)->toBe(4.0)
        ->and((float) $value->b)->toBe(8.0)
        ->and((float) $value->c)->toEqualWithDelta(M_PI, 1e-9);
});

it('loads the app translations even if a string was translated while booting', function () {
    $line = bootSeparately(
        fn (Application $app) => $app['translator']->get('Accueil', [], 'fr'),
        fn (Application $app) => $app['translator']->choice(':count avis|:count avis', 7, ['count' => 7], 'fr'),
    );

    expect($line)->toBe('7 avis');
});

it('shows French plural forms on a French business page', function () {
    $business = Business::factory()->for(City::factory()->create(['slug' => 'ma-ville']))->create();
    Review::factory()->for($business)->count(2)->create();
    app(RankingService::class)->recalculate($business->fresh());

    $this->get('/fr/ma-ville/b/'.$business->slug)->assertOk()->assertSee('(2 avis)')->assertDontSee('2 reviews');
});
