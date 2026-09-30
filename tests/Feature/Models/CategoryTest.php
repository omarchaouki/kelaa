<?php

use App\Models\Category;

it('allows a group and its children', function () {
    $group = Category::factory()->create();
    $child = Category::factory()->childOf($group)->create();

    expect($child->parent->is($group))->toBeTrue()
        ->and($group->children()->pluck('id')->all())->toBe([$child->id]);
});

it('refuses a third level', function () {
    $group = Category::factory()->create();
    $child = Category::factory()->childOf($group)->create();

    Category::factory()->childOf($child)->create();
})->throws(DomainException::class, 'two levels');

it('refuses to move a group with children under another group', function () {
    $group = Category::factory()->create();
    Category::factory()->childOf($group)->create();
    $other = Category::factory()->create();

    $group->update(['parent_id' => $other->id]);
})->throws(DomainException::class);

it('refuses a category as its own parent', function () {
    $category = Category::factory()->create();

    $category->update(['parent_id' => $category->id]);
})->throws(DomainException::class);

it('stores names in the three languages', function () {
    $category = Category::factory()->create(['name' => ['fr' => 'Plombier', 'ar' => 'سباك', 'en' => 'Plumber']]);

    app()->setLocale('ar');
    expect($category->fresh()->name)->toBe('سباك');

    app()->setLocale('en');
    expect($category->fresh()->name)->toBe('Plumber');
});
