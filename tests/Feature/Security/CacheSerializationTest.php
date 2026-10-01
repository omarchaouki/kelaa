<?php

use App\Enums\Role;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\PharmacyDuty;
use App\Models\Plan;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;

/**
 * The cache refuses to unserialize objects (cache.serializable_classes =
 * false, against object-injection gadget chains). Pages must therefore
 * cache only scalars/arrays: render everything twice through a real
 * serializing store to prove it.
 */
beforeEach(function () {
    $path = sys_get_temp_dir().'/city-cache-test-'.bin2hex(random_bytes(4));
    config(['cache.default' => 'file', 'cache.stores.file.path' => $path, 'cache.serializable_classes' => false]);
    Cache::forgetDriver('file');

    $this->city = City::factory()->create(['slug' => 'ma-ville']);
    $category = Category::factory()->childOf(Category::factory()->create())->create(['slug' => 'pharmacie-de-garde']);
    $this->business = Business::factory()->for($this->city)->inCategory($category)->create(['owner_id' => staff(Role::BusinessOwner)->id]);
    Review::factory()->for($this->business)->create();
    PharmacyDuty::factory()->for($this->business)->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHours(5)]);
    Plan::factory()->free()->create();
});

it('keeps objects out of the cache', function () {
    expect(config('cache.serializable_classes'))->toBeFalse();
});

it('serves pages from a serializing cache store', function (string $url) {
    $this->get($url)->assertOk();
    $this->get($url)->assertOk();   // second hit reads from the cache
})->with([
    '/fr/ma-ville',
    '/fr/ma-ville/pharmacies-de-garde',
    '/fr/ma-ville/pharmacie-de-garde',
]);

it('serves the owner dashboard from a serializing cache store', function () {
    $owner = $this->business->owner;

    $this->actingAs($owner)->get('/fr/dashboard/'.$this->business->id)->assertOk();
    $this->actingAs($owner)->get('/fr/dashboard/'.$this->business->id.'?tab=photos')->assertOk();
});
