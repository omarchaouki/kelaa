<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Fillable(['slug', 'title', 'body', 'meta_description', 'is_published'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['title', 'body', 'meta_description'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
