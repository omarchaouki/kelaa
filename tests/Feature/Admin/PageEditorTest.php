<?php

use App\Enums\Role;
use App\Models\Page;

it('opens a legal page that only has its French text', function () {
    $page = Page::query()->create([
        'slug' => 'cgu',
        'title' => ['fr' => 'CGU', 'ar' => 'شروط', 'en' => 'Terms'],
        'body' => ['fr' => '<p>Texte</p>', 'ar' => null, 'en' => null],
        'is_published' => true,
    ]);

    $this->actingAs(staff(Role::Admin))->get("/admin/pages/{$page->slug}/edit")->assertOk();
});
