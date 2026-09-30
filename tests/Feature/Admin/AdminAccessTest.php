<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Category;
use App\Models\City;
use App\Models\Plan;
use App\Models\User;

it('keeps guests out of the admin panel', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('forbids the admin panel to members and business owners', function (Role $role) {
    $this->actingAs(staff($role))->get('/admin')->assertForbidden();
})->with([Role::Member, Role::BusinessOwner]);

it('opens the admin panel to moderators and admins', function (Role $role) {
    $this->actingAs(staff($role))->get('/admin')->assertOk();
})->with([Role::Moderator, Role::Admin]);

it('forbids the admin panel to banned staff', function () {
    $this->actingAs(User::factory()->admin()->banned()->create())->get('/admin')->assertForbidden();
});

it('lets admins manage the catalog, plans and users', function (string $url) {
    City::factory()->create();

    $this->actingAs(staff(Role::Admin))->get($url)->assertOk();
})->with([
    '/admin/cities',
    '/admin/neighborhoods',
    '/admin/categories',
    '/admin/pages',
    '/admin/search-synonyms',
    '/admin/plans',
    '/admin/users',
]);

it('keeps moderators away from plans, catalog and users', function (string $url) {
    $this->actingAs(staff(Role::Moderator))->get($url)->assertForbidden();
})->with([
    '/admin/plans',
    '/admin/plans/create',
    '/admin/cities',
    '/admin/categories',
    '/admin/users',
]);

it('never lets moderators touch payments or plans', function () {
    $moderator = staff(Role::Moderator);

    expect($moderator->can(Permission::ManagePayments->value))->toBeFalse()
        ->and($moderator->can(Permission::ManagePlans->value))->toBeFalse()
        ->and($moderator->can('update', Plan::factory()->create()))->toBeFalse()
        ->and($moderator->can(Permission::ModerateReviews->value))->toBeTrue();
});

it('gives admins every permission and members none', function () {
    $admin = staff(Role::Admin);
    $member = member();

    foreach (Permission::cases() as $permission) {
        expect($admin->can($permission->value))->toBeTrue()
            ->and($member->can($permission->value))->toBeFalse();
    }
});

it('opens the Horizon dashboard to admins only', function () {
    $this->actingAs(staff(Role::Moderator))->get('/horizon')->assertForbidden();
    $this->actingAs(staff(Role::Admin))->get('/horizon')->assertOk();
});

it('renders the category edit form with one input per language', function () {
    $category = Category::factory()->create(['slug' => 'plombier', 'name' => ['fr' => 'Plombier', 'ar' => 'سباك', 'en' => 'Plumber']]);

    $this->actingAs(staff(Role::Admin))
        ->get("/admin/categories/{$category->slug}/edit")
        ->assertOk()
        ->assertSee('العربية')
        ->assertSee('English');
});
